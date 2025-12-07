<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class RestoreDatabaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'database' => [
                'required',
                File::types(['sql', 'sqlite', 'sqlite3', 'db'])
                    ->max(512 * 1024), // 500MB max
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'database.required' => 'Please select a database file to upload.',
            'database.max' => 'The database file must not exceed 500MB.',
        ];
    }
}
