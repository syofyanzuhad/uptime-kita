<?php

namespace Database\Seeders;

use App\Models\Monitor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Tags\Tag;

class MonitorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Tech' => require database_path('seeders/monitors/tech.php'),
            'University' => require database_path('seeders/monitors/universities.php'),
            'Government' => require database_path('seeders/monitors/government.php'),
            'Fintech' => require database_path('seeders/monitors/fintech.php'),
            'E-Commerce' => require database_path('seeders/monitors/ecommerce.php'),
            'News' => require database_path('seeders/monitors/news.php'),
        ];

        // 1. Prepare unique monitors for bulk upsert
        $allMonitors = [];
        foreach ($categories as $urls) {
            foreach ($urls as $url) {
                $allMonitors[$url] = [
                    'url' => $url,
                    'uptime_check_enabled' => 1,
                    'certificate_check_enabled' => 1,
                    'is_public' => 1,
                    'uptime_check_interval_in_minutes' => 1,
                ];
            }
        }

        // 2. Fast bulk upsert monitors in chunks of 250
        foreach (array_chunk(array_values($allMonitors), 250) as $chunk) {
            DB::table('monitors')->upsert(
                $chunk,
                ['url'],
                ['certificate_check_enabled', 'is_public', 'uptime_check_interval_in_minutes']
            );
        }

        // 3. Fetch all monitor IDs mapped by URL
        $monitorsByUrl = Monitor::withoutGlobalScopes()->pluck('id', 'url');
        $morphType = (new Monitor)->getMorphClass();

        // 4. Bulk attach/sync tags to taggables
        $taggables = [];
        foreach ($categories as $tagName => $urls) {
            $tag = Tag::findOrCreate($tagName);

            foreach ($urls as $url) {
                if (isset($monitorsByUrl[$url])) {
                    $taggables[] = [
                        'tag_id' => $tag->id,
                        'taggable_type' => $morphType,
                        'taggable_id' => $monitorsByUrl[$url],
                    ];
                }
            }
        }

        // 5. Fast insert tag relationships in chunks
        foreach (array_chunk($taggables, 250) as $chunk) {
            DB::table('taggables')->insertOrIgnore($chunk);
        }
    }
}
