<?php

namespace Database\Seeders;

use App\Models\NotificationChannel;
use App\Models\User;
use Illuminate\Database\Seeder;

class NotificationChannelSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();

        if (! $user) {
            $this->command->info('No users found, skipping notification channel seeding.');

            return;
        }

        NotificationChannel::firstOrCreate([
            'user_id' => $user->id,
        ], [
            'type' => 'telegram',
            'destination' => env('TELEGRAM_DEFAULT_CHAT_ID', '-1002075830228'), // chat ID Telegram
            'metadata' => ['note' => 'Telegram utama'],
        ]);

        NotificationChannel::firstOrCreate([
            'user_id' => $user->id,
        ], [
            'type' => 'email',
            'destination' => $user->email,
            'metadata' => ['preferred' => true],
        ]);
    }
}
