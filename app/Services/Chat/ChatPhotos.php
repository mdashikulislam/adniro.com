<?php

namespace App\Services\Chat;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

/*
 * Photos sent in a chat (adniro). They are kept on the private disk, never under a public link, and only
 * opened through the chat's own file route for the conversation's participants. Every photo is decoded and
 * saved again as a JPEG, which drops its location and camera data and anything that is not an image.
 */
class ChatPhotos
{
	public const DISK = 'private';
	public const FOLDER = 'chat';
	public const MAX_UPLOAD_KB = 8192;
	public const MIMES = 'jpg,jpeg,png,webp';

	/** The browser sends photos no larger than 1600px; anything far beyond that is refused before it is decoded. */
	public const MAX_SIDE = 4096;

	private const FULL_SIDE = 1600;
	private const THUMB_SIDE = 480;
	private const QUALITY = 82;
	private const THUMB_SUFFIX = '_t';

	public static function disk(): Filesystem
	{
		return Storage::disk(self::DISK);
	}

	/** Saves the photo and its thumbnail; returns the photo's path on the private disk. */
	public static function store(UploadedFile $file, int $threadId): string
	{
		$path = self::FOLDER . '/' . $threadId . '/' . Str::uuid()->toString() . '.jpg';

		// Decoded once; the thumbnail is made from the already smaller photo
		$image = Image::read($file->getRealPath())->scaleDown(width: self::FULL_SIDE, height: self::FULL_SIDE);
		$full = $image->toJpeg(self::QUALITY);
		$thumb = $image->scaleDown(width: self::THUMB_SIDE, height: self::THUMB_SIDE)->toJpeg(self::QUALITY);

		self::disk()->put($path, (string)$full);
		self::disk()->put(self::thumbPath($path), (string)$thumb);

		return $path;
	}

	public static function thumbPath(string $path): string
	{
		return preg_replace('/\.jpg$/', self::THUMB_SUFFIX . '.jpg', $path);
	}

	public static function isChatPath(?string $path): bool
	{
		return is_string($path) && str_starts_with(ltrim($path, '/'), self::FOLDER . '/');
	}

	public static function delete(?string $path): void
	{
		if (!self::isChatPath($path)) {
			return;
		}

		self::disk()->delete([$path, self::thumbPath($path)]);
	}
}
