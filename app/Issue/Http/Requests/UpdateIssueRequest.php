<?php

namespace App\Issue\Http\Requests;

use App\Issue\Enums\Status;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIssueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $cleaned = [];

        foreach (['title', 'description', 'issuer'] as $field) {
            if ($this->has($field)) {
                $cleaned[$field] = $this->stripTags($this->input($field));
            }
        }

        if ($cleaned !== []) {
            $this->merge($cleaned);
        }
    }

    private function stripTags(mixed $value): ?string
    {
        return is_string($value) ? strip_tags($value) : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title'        => ['sometimes', 'required', 'string', 'max:255'],
            'description'  => ['sometimes', 'required', 'string'],
            'category'     => ['sometimes', 'required', 'string', 'exists:categories,slug'],
            'status'       => ['sometimes', 'required', Rule::enum(Status::class)],
            'due_date'     => ['sometimes', 'nullable', 'date'],
            'issuer'       => ['sometimes', 'nullable', 'string', 'max:255'],
            'issuer_email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'if_unmodified_since' => ['sometimes', 'date'],
        ];
    }
}
