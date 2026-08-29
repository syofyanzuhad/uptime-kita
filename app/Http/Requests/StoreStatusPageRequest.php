<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreStatusPageRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:1000'],
            'icon' => ['required', 'string', 'max:255'],
            'path' => [
                'nullable',
                'string',
                'max:255',
                'unique:status_pages,path',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
            ],
            'custom_domain' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^(?!:\/\/)(?=.{1,255}$)((.{1,63}\.){1,127}(?![0-9]*$)[a-z0-9-]+\.?)$/i',
                'unique:status_pages,custom_domain',
            ],
            'force_https' => ['boolean'],
        ];
    }
}
