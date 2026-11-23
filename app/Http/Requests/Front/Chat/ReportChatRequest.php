<?php

namespace App\Http\Requests\Front\Chat;

use App\Models\Chat\ThreadReport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/* Reporting a conversation to the admins (adniro). */
class ReportChatRequest extends FormRequest
{
	public function authorize(): bool
	{
		return $this->user() !== null;
	}

	protected function prepareForValidation(): void
	{
		if ($this->filled('note')) {
			$this->merge(['note' => trim(strip_tags((string)$this->input('note')))]);
		}
	}

	public function rules(): array
	{
		return [
			'reason' => ['required', Rule::in(ThreadReport::REASONS)],
			'note'   => ['nullable', 'string', 'max:500'],
		];
	}
}
