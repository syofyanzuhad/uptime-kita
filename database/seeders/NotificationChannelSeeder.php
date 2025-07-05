<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\NotificationChannel;

class NotificationChannelSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first(); // contoh pakai user pertama

        NotificationChannel::firstOrCreate([
            'user_id' => $user->id,
        ], [
            'type' => 'telegram',
            'destination' => '-1002075830228', // chat ID Telegram
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
