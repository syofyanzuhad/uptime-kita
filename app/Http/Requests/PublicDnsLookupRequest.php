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
            'server' => [
                'nullable',
                'string',
                'ip',
                'not_regex:/^(10\.|172\.(1[6-9]|2[0-9]|3[01])\.|192\.168\.|127\.|169\.254\.|0\.|fc00:|fe80:|::1)/',
            ],
            'check_global' => ['nullable', 'boolean'],
        ];
    }
}
