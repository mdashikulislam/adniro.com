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

use App\Helpers\Common\Arr;
use App\Http\Requests\Front\ContactRequest;
use App\Http\Requests\Front\ReportRequest;
use App\Http\Resources\PostResource;
use App\Models\Permission;
use App\Models\Post;
use App\Models\User;
use App\Notifications\FormSent;
use App\Notifications\ReportSent;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Notification;
use Throwable;

class ContactService extends BaseService
{
	/**
	 * Send a message to the site owner
	 *
	 * @param \App\Http\Requests\Front\ContactRequest $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function submitContactForm(ContactRequest $request): JsonResponse
	{
		// Store Contact Input
		$contactForm = $request->all();
		$contactForm = Arr::toObject($contactForm);
		
		// Send Contact Email
		try {
			if (!config('settings.app.email')) {
				Notification::route('mail', config('settings.app.email'))->notify(new FormSent($contactForm));
			} else {
				$admins = User::permission(Permission::getStaffPermissions())->get();
				if ($admins->count() > 0) {
					Notification::send($admins, new FormSent($contactForm));
				}
			}
			
			$data = [
				'success' => true,
				'message' => t('message_sent_to_moderators_thanks'),
				'result'  => $contactForm,
			];
			
			return apiResponse()->json($data);
		} catch (Throwable $e) {
			return apiResponse()->error($e->getMessage());
		}
	}
	
	/**
	 * Report abuse or issues
	 *
	 * @param $postId
	 * @param \App\Http\Requests\Front\ReportRequest $request
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function submitReportForm($postId, ReportRequest $request): JsonResponse
	{
		// Get Post
		$post = Post::find($postId);
		
		if (empty($post)) {
			return apiResponse()->notFound(t('post_not_found'));
		}
		
		// Store Report Input
		$report = $request->all();
		$report = Arr::toObject($report);
		
		// Send Abuse Report to admin
		try {
			if (config('settings.app.email')) {
				Notification::route('mail', config('settings.app.email'))->notify(new ReportSent($post, $report));
			} else {
				$admins = User::permission(Permission::getStaffPermissions())->get();
				if ($admins->count() > 0) {
					Notification::send($admins, new ReportSent($post, $report));
				}
			}
			
			$data = [
				'success' => true,
				'message' => t('report_has_sent_successfully_to_us'),
				'result'  => $report,
				'extra'   => [
					'post' => (new PostResource($post))->toArray($request),
				],
			];
			
			return apiResponse()->json($data);
		} catch (Throwable $e) {
			return apiResponse()->error($e->getMessage());
		}
	}
}
