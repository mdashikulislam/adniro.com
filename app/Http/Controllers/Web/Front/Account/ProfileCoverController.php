<?php

namespace App\Http\Controllers\Web\Front\Account;

use App\Helpers\Common\Files\Storage\StorageDisk;
use App\Helpers\Common\Files\Upload;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

/*
 * Cover image of the logged user, shown at the top of the seller's public page (/user/{username})
 */
class ProfileCoverController extends Controller
{
	/**
	 * Upload (or replace) the cover image
	 *
	 * @param \Illuminate\Http\Request $request
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function update(Request $request): RedirectResponse
	{
		$request->validate(
			[
				'cover' => [
					'required',
					'file',
					'mimes:' . collect(getServerAllowedImageFormats())->join(','),
					'max:' . (int)config('settings.upload.max_image_size', 1000),
				],
			],
			[],
			['cover' => mb_strtolower(t('cover_image'))]
		);
		
		$userId = (int)auth()->id();
		$oldPath = $this->currentPath($userId);
		
		// Large enough for the page's full width, proportions kept (the page crops it to its banner)
		try {
			$path = Upload::image($request->file('cover'), 'covers/' . $userId, [
				'width'  => 1920,
				'height' => 1200,
				'ratio'  => '1',
				'upsize' => '0',
			]);
		} catch (Throwable $e) {
			flash($e->getMessage())->error();
			
			return redirect()->to(urlGen()->accountProfile());
		}
		
		if (empty($path)) {
			flash(t('unknown_error'))->error();
			
			return redirect()->to(urlGen()->accountProfile());
		}
		
		// Query update: no model event (nothing but this column changes)
		User::query()->withoutGlobalScopes()->where('id', $userId)->update(['cover_path' => $path]);
		$this->deleteFile($oldPath, $path);
		
		flash(t('cover_image_updated'))->success();
		
		return redirect()->to(urlGen()->accountProfile());
	}
	
	/**
	 * Remove the cover image
	 *
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function destroy(): RedirectResponse
	{
		$userId = (int)auth()->id();
		$oldPath = $this->currentPath($userId);
		
		User::query()->withoutGlobalScopes()->where('id', $userId)->update(['cover_path' => null]);
		$this->deleteFile($oldPath);
		
		flash(t('cover_image_removed'))->success();
		
		return redirect()->to(urlGen()->accountProfile());
	}
	
	/**
	 * @param int $userId
	 * @return string|null
	 */
	private function currentPath(int $userId): ?string
	{
		return User::query()->withoutGlobalScopes()->where('id', $userId)->value('cover_path');
	}
	
	/**
	 * Delete a former cover file (only inside the covers' directory)
	 *
	 * @param string|null $path
	 * @param string|null $exceptPath
	 * @return void
	 */
	private function deleteFile(?string $path, ?string $exceptPath = null): void
	{
		if (empty($path) || $path === $exceptPath || !str_starts_with($path, 'covers/') || str_contains($path, '..')) {
			return;
		}
		
		try {
			$disk = StorageDisk::getDisk();
			if ($disk->exists($path)) {
				$disk->delete($path);
			}
		} catch (Throwable $e) {
		}
	}
}
