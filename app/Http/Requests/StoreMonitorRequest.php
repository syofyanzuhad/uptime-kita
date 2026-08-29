<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreMonitorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('url') && is_string($this->url)) {
            $url = rtrim(filter_var($this->url, FILTER_VALIDATE_URL) ?: $this->url, '/');

            if (str_starts_with($url, 'http://')) {
                $url = 'https://'.substr($url, 7);
            }

            $this->merge(['url' => $url]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'url' => ['required', 'url'],
            'is_public' => ['nullable', 'boolean'],
            'uptime_check_enabled' => ['nullable', 'boolean'],
            'certificate_check_enabled' => ['nullable', 'boolean'],
            'domain_expiration_check_enabled' => ['nullable', 'boolean'],
            'uptime_check_interval' => ['required', 'integer', 'min:1'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:255'],
        ];
    }
}
