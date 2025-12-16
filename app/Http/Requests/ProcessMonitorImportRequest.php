<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProcessMonitorImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rows' => ['required', 'array', 'min:1'],
            // URL validation is relaxed here since error rows may have invalid URLs
            // The service will skip error rows during import
            'rows.*.url' => ['nullable', 'string'],
            'rows.*.display_name' => ['nullable', 'string', 'max:255'],
            'rows.*.uptime_check_enabled' => ['nullable'],
            'rows.*.certificate_check_enabled' => ['nullable'],
            'rows.*.uptime_check_interval' => ['nullable', 'integer', 'min:1', 'max:60'],
            'rows.*.is_public' => ['nullable'],
            'rows.*.sensitivity' => ['nullable', 'string', 'in:low,medium,high'],
            'rows.*.expected_status_code' => ['nullable', 'integer', 'min:100', 'max:599'],
            'rows.*.tags' => ['nullable', 'array'],
            'rows.*.tags.*' => ['string', 'max:255'],
            'rows.*._row_number' => ['required', 'integer'],
            'rows.*._status' => ['required', 'string', 'in:valid,error,duplicate'],
            'rows.*._errors' => ['nullable', 'array'],
            'duplicate_action' => ['required', 'string', 'in:skip,update,create'],
            'resolutions' => ['nullable', 'array'],
            'resolutions.*' => ['string', 'in:skip,update,create'],
        ];
    }

    public function messages(): array
    {
        return [
            'rows.required' => 'No monitors to import.',
            'rows.min' => 'At least one monitor is required.',
            'duplicate_action.required' => 'Please select how to handle duplicate monitors.',
        ];
    }
}
