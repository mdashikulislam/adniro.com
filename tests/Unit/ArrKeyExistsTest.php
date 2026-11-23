<?php

namespace Tests\Unit;

use App\Helpers\Common\Arr;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;

/*
 * Arr::keyExists() on a model answers what toArray() would, without building the array (adniro): building it
 * computed every appended field and nested relation of a listing just to find one key.
 */
class ArrKeyExistsTest extends TestCase
{
	public function test_a_model_answers_like_its_array_would(): void
	{
		// Arrange: attributes (one hidden), an appended field, a loaded relation
		$model = $this->listing();
		$model->setRelation('firstPicture', $this->listing());
		$asArray = $model->toArray();
		$model->computed = 0;

		// Act & Assert
		foreach (['id', 'email_verified_at', 'reviewed_at', 'nice_title', 'first_picture', 'secret', 'firstPicture', 'missing', ''] as $key) {
			$this->assertSame(array_key_exists($key, $asArray), Arr::keyExists($key, $model), 'key: ' . $key);
		}
		$this->assertTrue(Arr::keyExists('reviewed_at', $model), 'a key that holds null is still there');
		$this->assertFalse(Arr::keyExists('secret', $model), 'a hidden attribute is not');
		$this->assertSame(0, $model->computed, 'no appended field was computed to answer');
	}

	public function test_a_model_that_lists_what_it_shows_answers_for_that_list_only(): void
	{
		// Arrange
		$model = $this->listing()->setVisible(['id', 'nice_title']);

		// Act & Assert
		$this->assertTrue(Arr::keyExists('id', $model));
		$this->assertTrue(Arr::keyExists('nice_title', $model));
		$this->assertFalse(Arr::keyExists('email_verified_at', $model));
		$this->assertSame(array_keys($model->toArray()), ['id', 'nice_title']);
	}

	public function test_arrays_objects_and_json_answer_as_before(): void
	{
		$this->assertTrue(Arr::keyExists('a', ['a' => null]));
		$this->assertFalse(Arr::keyExists('b', ['a' => 1]));
		$this->assertTrue(Arr::keyExists('a', (object)['a' => 1]));
		$this->assertTrue(Arr::keyExists('a', '{"a":1}'));
		$this->assertTrue(Arr::keyExists('a', collect(['a' => 1])));
		$this->assertFalse(Arr::keyExists('a', 12));
	}

	private function listing(): Model
	{
		$model = new class extends Model {
			public int $computed = 0;
			protected $guarded = [];
			protected $hidden = ['secret'];
			protected $appends = ['nice_title'];

			public function getNiceTitleAttribute(): string
			{
				$this->computed++;

				return 'Nice';
			}
		};

		return $model->setRawAttributes(['id' => 5, 'email_verified_at' => '2026-01-01', 'reviewed_at' => null, 'secret' => 'x']);
	}
}
