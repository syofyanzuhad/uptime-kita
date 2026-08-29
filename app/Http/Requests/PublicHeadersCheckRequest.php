<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PublicHeadersCheckRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'url' => ['required', 'string', 'max:255'],
        ];
    }
}
