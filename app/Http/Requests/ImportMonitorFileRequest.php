<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportMonitorFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'import_file' => [
                'required',
                'file',
                'mimes:csv,txt,json',
                'max:10240', // 10MB max
            ],
            'format' => [
                'nullable',
                'string',
                'in:csv,json',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'import_file.required' => 'Please select a file to import.',
            'import_file.mimes' => 'Only CSV and JSON files are supported.',
            'import_file.max' => 'The file must not exceed 10MB.',
        ];
    }
}
