<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PublicDnsLookupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'domain' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'in:ALL,A,AAAA,MX,TXT,CNAME,NS,SOA'],
        ];
    }
}
