<?php
/*
 * LaraClassifier - Classified Ads Web Application
 * Copyright (c) BeDigit. All Rights Reserved
 *
 * Website: https://laraclassifier.com
 * Author: Mayeul Akpovi (BeDigit - https://bedigit.com)
 *
 * LICENSE
 * -------
 * This software is provided under a license agreement and may only be used or copied
 * in accordance with its terms, including the inclusion of the above copyright notice.
 * As this software is sold exclusively on CodeCanyon,
 * please review the full license details here: https://codecanyon.net/licenses/standard
 */

namespace App\Services;

// Increase the server resources
$iniConfigFile = __DIR__ . '/../Helpers/Common/Functions/ini.php';
if (file_exists($iniConfigFile)) {
	$configForUpload = true;
	include_once $iniConfigFile;
}

use App\Helpers\Common\Arr;
use App\Http\Requests\Front\PostRequest;
use App\Http\Requests\Front\PostRequest\LimitationCompliance;
use App\Models\Post;
use App\Models\Scopes\ReviewedScope;
use App\Models\Scopes\VerifiedScope;
use App\Notifications\PostDeleted;
use App\Services\Auth\Traits\Custom\VerificationTrait;
use App\Services\Payment\HasPaymentTrigger;
use App\Services\Payment\Promotion\SingleStepPayment;
use App\Services\Picture\SingleStepPictures;
use App\Services\Post\ListTrait;
use App\Services\Post\ShowTrait;
use App\Services\Post\StoreTrait;
use App\Services\Post\UpdateTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use NotificationChannels\Twilio\TwilioChannel;
use Throwable;

class PostService extends BaseService
{
	use VerificationTrait;
	
	use ListTrait;
	use ShowTrait;
	use StoreTrait;
	use UpdateTrait;
	
	use SingleStepPictures;
	use SingleStepPayment, HasPaymentTrigger;
	
	/**
	 * List listings
	 *
	 * @param array $params
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getEntries(array $params = []): JsonResponse
	{
		// Advanced Query (Query with the 'op' parameter)
		$searchOptions = ['search', 'premium', 'latest', 'free', 'premiumFirst'];
		
		$op = $params['op'] ?? null;
		$op = is_string($op) ? $op : null;
		
		if (in_array($op, $searchOptions)) {
			return $this->getPostsBySearch($op, $params);
		}
		if ($op == 'similar') {
			return $this->getSimilarPosts($params);
		}
		
		return $this->getPostsList($params);
	}
	
	/**
	 * Get listing
	 *
	 * @param $id
	 * @param array $params
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getEntry($id, array $params = []): JsonResponse
	{
		$embed = castCommaSeparatedStrToArray($params['embed'] ?? []);
		$isDetailed = castIntToBool($params['detailed'] ?? 0);
		
		if ($isDetailed) {
			$defaultEmbed = [
				'user',
				'category',
				'parent',
				'postType',
				'city',
				'subAdmin1',
				'currency',
				'savedByLoggedUser',
				'picture',
				'pictures',
				'payment',
				'package',
			];
			$params['embed'] = !empty($embed) ? array_merge($defaultEmbed, $embed) : $defaultEmbed;
			
			return $this->showDetailedPost($id, $params);
		}
		
		return $this->showPost($id, $params);
	}
	
	/**
	 * Store listing
	 *
	 * For both types of listing's creation (Single step or Multi steps).
	 * Note: The field 'admin_code' is only available when the listing's country's 'admin_type' column is set to 1 or 2.
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\JsonResponse|mixed
	 */
	public function store(Request $request)
	{
		$this->setPaymentSettingsForPromotion();
		
		return $this->storePost($request);
	}
	
	/**
	 * Update listing
	 *
	 * Note: The fields 'pictures', 'package_id' and 'payment_method_id' are only available with the single step listing edition.
	 * The field 'admin_code' is only available when the listing's country's 'admin_type' column is set to 1 or 2.
	 *
	 * @param $id
	 * @param \App\Http\Requests\Front\PostRequest $request
	 * @return array|\Illuminate\Http\JsonResponse|mixed
	 */
	public function update($id, PostRequest $request)
	{
		// Single-Step Form
		if (isSingleStepFormEnabled()) {
			$this->setPaymentSettingsForPromotion();
			
			return $this->singleStepFormUpdate($id, $request);
		}
		
		return $this->multiStepsFormUpdate($id, $request);
	}
	
	/**
	 * Delete listing(s)
	 *
	 * @param string $ids
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function destroy(string $ids): JsonResponse
	{
		$authUser = auth(getAuthGuard())->user();
		
		if (empty($authUser)) {
			return apiResponse()->unauthorized();
		}
		
		$data = [
			'success' => false,
			'message' => t('no_deletion_is_done'),
			'result'  => null,
		];
		
		$extra = [];
		
		// Get Entries ID (IDs separated by comma accepted)
		$ids = explode(',', $ids);
		
		// Delete
		$res = false;
		foreach ($ids as $postId) {
			$post = Post::withoutGlobalScopes([VerifiedScope::class, ReviewedScope::class])
				->where('user_id', $authUser->getAuthIdentifier())
				->where('id', $postId)
				->first();
			
			if (!empty($post)) {
				$tmpPost = Arr::toObject($post->toArray());
				
				// Delete Entry
				$res = $post->delete();
				
				// Send an Email or SMS confirmation
				$emailNotificationCanBeSent = (config('settings.mail.confirmation') == '1' && !empty($tmpPost->email));
				$smsNotificationCanBeSent = (
					isPhoneAsAuthFieldEnabled()
					&& config('settings.sms.confirmation') == '1'
					&& $tmpPost->auth_field == 'phone'
					&& !empty($tmpPost->phone)
					&& !isDemoDomain()
				);
				try {
					if ($emailNotificationCanBeSent) {
						Notification::route('mail', $tmpPost->email)->notify(new PostDeleted($tmpPost));
					}
					if ($smsNotificationCanBeSent) {
						$smsChannel = (config('settings.sms.driver') == 'twilio')
							? TwilioChannel::class
							: 'vonage';
						Notification::route($smsChannel, $tmpPost->phone)->notify(new PostDeleted($tmpPost));
					}
				} catch (Throwable $e) {
					$extra['mail']['success'] = false;
					$extra['mail']['message'] = $e->getMessage();
				}
			}
		}
		
		// Confirmation
		if ($res) {
			$data['success'] = true;
			
			$count = count($ids);
			if ($count > 1) {
				$data['message'] = t('x entities have been deleted successfully', ['entities' => t('listings'), 'count' => $count]);
			} else {
				$data['message'] = t('1 entity has been deleted successfully', ['entity' => t('listing')]);
			}
		}
		
		$data['extra'] = $extra;
		
		return apiResponse()->json($data);
	}
	
	/**
	 * Archive a listing
	 *
	 * Put a listing offline
	 *
	 * @authenticated
	 * @header Authorization Bearer {YOUR_AUTH_TOKEN}
	 *
	 * @urlParam id int required The post/listing's ID.
	 *
	 * @param $id
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function offline($id): JsonResponse
	{
		return $this->takePostOffline($id);
	}
	
	/**
	 * Repost a listing
	 *
	 * Repost a listing by un-archiving it.
	 *
	 * @authenticated
	 * @header Authorization Bearer {YOUR_AUTH_TOKEN}
	 *
	 * @urlParam id int required The post/listing's ID.
	 *
	 * @param $id
	 * @param \App\Http\Requests\Front\PostRequest\LimitationCompliance $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function repost($id, LimitationCompliance $request): JsonResponse
	{
		return $this->repostPost($id, $request);
	}
}
