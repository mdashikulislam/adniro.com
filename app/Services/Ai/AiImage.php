<?php

namespace App\Services\Ai;

use App\Helpers\Common\Files\Storage\StorageDisk;
use Intervention\Image\Laravel\Facades\Image as ImageProcessor;
use Laravel\Ai\Files\Base64Image;
use Laravel\Ai\Files\Image;
use Throwable;

/*
 * Turns a stored picture into an image attachment for an AI request (adniro:ai).
 * Pictures are resized to a 1024 px long edge and re-encoded as JPEG to keep requests small.
 */

class AiImage
{
	public static function fromStorage(string $path, $disk = null): ?Base64Image
	{
		try {
			$disk ??= StorageDisk::getDisk();
			if (!$disk->exists($path)) {
				return null;
			}
			
			return self::fromBinary($disk->get($path));
		} catch (Throwable $e) {
			return null;
		}
	}
	
	public static function fromLocalPath(string $path): ?Base64Image
	{
		if (!is_file($path) || !is_readable($path)) {
			return null;
		}
		
		return self::fromBinary((string)file_get_contents($path));
	}
	
	public static function fromBinary(string $binary): ?Base64Image
	{
		if ($binary === '') {
			return null;
		}
		
		try {
			$maxEdge = (int)config('ai_features.image.max_edge', 1024);
			$quality = (int)config('ai_features.image.quality', 80);
			
			$jpeg = ImageProcessor::read($binary)
				->orient()
				->scaleDown($maxEdge, $maxEdge)
				->toJpeg($quality)
				->toString();
			
			return Image::fromBase64(base64_encode($jpeg), 'image/jpeg');
		} catch (Throwable $e) {
			return null;
		}
	}
}
