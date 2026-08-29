<?php

namespace App\Http\Requests;

use App\Models\StatusPage;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStatusPageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $statusPage = $this->route('status_page');

        if ($statusPage instanceof StatusPage) {
            return $this->user()?->can('update', $statusPage) ?? false;
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $statusPageId = $this->route('status_page')?->id ?? $this->route('status_page');

        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string', 'max:1000'],
            'icon' => ['sometimes', 'required', 'string', 'max:255'],
            'path' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('status_pages', 'path')->ignore($statusPageId),
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            ],
        ];
    }
}
