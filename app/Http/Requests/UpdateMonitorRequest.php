<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMonitorRequest extends FormRequest
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
        $monitorId = $this->route('monitor')?->id ?? $this->route('monitor');

        return [
            'url' => ['required', 'url', Rule::unique('monitors', 'url')->ignore($monitorId)],
            'is_public' => ['nullable', 'boolean'],
            'uptime_check_enabled' => ['nullable', 'boolean'],
            'certificate_check_enabled' => ['nullable', 'boolean'],
            'domain_expiration_check_enabled' => ['nullable', 'boolean'],
            'uptime_check_interval' => ['required', 'integer', 'min:1'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:255'],
            'sensitivity' => ['nullable', 'string', 'in:low,medium,high'],
            'confirmation_delay_seconds' => ['nullable', 'integer', 'min:5', 'max:300'],
            'confirmation_retries' => ['nullable', 'integer', 'min:1', 'max:10'],
        ];
    }
}
