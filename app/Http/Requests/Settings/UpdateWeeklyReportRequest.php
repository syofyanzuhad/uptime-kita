<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWeeklyReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<mixed>>
     */
    public function rules(): array
    {
        return [
            'weekly_report_enabled' => ['required', 'boolean'],
            'weekly_report_timezone' => ['nullable', 'string', 'max:64', Rule::in(\DateTimeZone::listIdentifiers())],
        ];
    }
}
