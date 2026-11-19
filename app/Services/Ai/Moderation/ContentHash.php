<?php

namespace App\Services\Ai\Moderation;

use App\Models\Picture;
use App\Models\Post;
use App\Models\PostValue;

/*
 * Fingerprint of the parts of a listing that moderation looks at (adniro:ai).
 * Reordering photos, paying, reposting or verifying does not change it.
 * A decision is reused as long as the fingerprint is the same.
 */

final class ContentHash
{
	/** Post columns that send a listing for a new check when they change. */
	public const POST_COLUMNS = [
		'title',
		'description',
		'tags',
		'price',
		'currency_code',
		'category_id',
		'post_type_id',
		'contact_name',
	];
	
	public static function for(Post $post): string
	{
		$raw = $post->getAttributes();
		
		$columns = [];
		foreach (self::POST_COLUMNS as $column) {
			$value = $raw[$column] ?? null;
			if ($column === 'price') {
				$value = ($value === null || $value === '') ? null : (string)(float)$value;
			}
			$columns[$column] = ($value === null) ? null : trim((string)$value);
		}
		
		$pictures = Picture::query()
			->withoutGlobalScopes()
			->where('post_id', $post->getKey())
			->pluck('file_path')
			->map(fn ($path) => (string)$path)
			->sort()
			->values()
			->all();
		
		$values = PostValue::query()
			->withoutGlobalScopes()
			->where('post_id', $post->getKey())
			->get(['field_id', 'option_id', 'value'])
			->map(fn ($row) => [(int)$row->field_id, $row->option_id === null ? null : (int)$row->option_id, (string)$row->value])
			->sort()
			->values()
			->all();
		
		$payload = json_encode(
			['post' => $columns, 'pictures' => $pictures, 'fields' => $values],
			JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
		);
		
		return hash('sha256', (string)$payload);
	}
}
