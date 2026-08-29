<?php

namespace App\Http\Requests;

use App\Models\NotificationChannel;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationChannelRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $channel = $this->route('notification') ?? $this->route('notification_channel');

        if ($channel instanceof NotificationChannel) {
            return $channel->user_id === $this->user()?->id;
        }

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
            'type' => ['required', 'string'],
            'destination' => ['required', 'string'],
            'is_enabled' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
