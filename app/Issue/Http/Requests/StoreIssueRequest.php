<?php

namespace App\Issue\Http\Requests;

use App\Issue\Enums\Status;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIssueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'title'       => $this->stripTags($this->input('title')),
            'description' => $this->stripTags($this->input('description')),
            'issuer'      => $this->stripTags($this->input('issuer')),
        ]);
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
            'title'        => ['required', 'string', 'max:255'],
            'description'  => ['required', 'string'],
            'category'     => ['required', 'string', 'exists:categories,slug'],
            'status'       => ['sometimes', Rule::enum(Status::class)],
            'due_date'     => ['nullable', 'date'],
            'issuer'       => ['nullable', 'string', 'max:255'],
            'issuer_email' => ['nullable', 'email', 'max:255'],
        ];
    }
}
