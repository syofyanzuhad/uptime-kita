<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomDomainRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $statusPage = $this->route('statusPage') ?? $this->route('status_page');

        return $statusPage && $statusPage->user_id === $this->user()?->id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $statusPageId = $this->route('statusPage')?->id ?? $this->route('statusPage') ?? $this->route('status_page')?->id ?? $this->route('status_page');

        return [
            'custom_domain' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^(?!:\/\/)(?=.{1,255}$)((.{1,63}\.){1,127}(?![0-9]*$)[a-z0-9-]+\.?)$/i',
                Rule::unique('status_pages', 'custom_domain')->ignore($statusPageId),
            ],
            'force_https' => ['boolean'],
        ];
    }
}
