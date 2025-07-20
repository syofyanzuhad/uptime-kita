<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::firstOrCreate([
            'email' => config('app.admin_credentials.email'),
        ], [
            'name' => 'Syofyan Zuhad',
            'password' => bcrypt(config('app.admin_credentials.password')),
            'email_verified_at' => now(),
        ]);

        $this->call([
            MonitorSeeder::class,
            // NotificationChannelSeeder::class,
        ]);
    }
}
