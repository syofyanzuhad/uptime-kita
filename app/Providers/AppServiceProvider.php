<?php

namespace App\Providers;

use Spatie\Health\Facades\Health;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Event;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Opcodes\LogViewer\Facades\LogViewer;
use Spatie\CpuLoadHealthCheck\CpuLoadCheck;
use Spatie\Health\Checks\Checks\CacheCheck;
use Spatie\Health\Checks\Checks\QueueCheck;
use Spatie\Health\Checks\Checks\RedisCheck;
use Spatie\Health\Checks\Checks\HorizonCheck;
use Spatie\Health\Checks\Checks\DatabaseCheck;
use Spatie\Health\Checks\Checks\ScheduleCheck;
use App\Listeners\SendCustomMonitorNotification;
use Spatie\Health\Checks\Checks\DatabaseSizeCheck;
use Spatie\Health\Checks\Checks\OptimizedAppCheck;
use Spatie\UptimeMonitor\Events\UptimeCheckFailed;
use Spatie\Health\Checks\Checks\UsedDiskSpaceCheck;
use Spatie\UptimeMonitor\Events\UptimeCheckRecovered;
use Spatie\Health\Checks\Checks\RedisMemoryUsageCheck;
use Spatie\Health\Checks\Checks\DatabaseTableSizeCheck;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::shouldBeStrict(! app()->isProduction());

        if (config('app.env') !== 'local') {
            URL::forceScheme('https');
        }

        LogViewer::auth(fn ($request) => auth()->id() === 1);

        // Register uptime monitor event listeners
        Event::listen(UptimeCheckFailed::class, SendCustomMonitorNotification::class);
        Event::listen(UptimeCheckRecovered::class, SendCustomMonitorNotification::class);

        Health::checks([
            CacheCheck::new(),
            OptimizedAppCheck::new(),
            DatabaseCheck::new(),
            UsedDiskSpaceCheck::new()
                ->warnWhenUsedSpaceIsAbovePercentage(70)
                ->failWhenUsedSpaceIsAbovePercentage(90),
            RedisCheck::new(),
            RedisMemoryUsageCheck::new()
                ->warnWhenAboveMb(900)
                ->failWhenAboveMb(1000),
            CpuLoadCheck::new()
                ->failWhenLoadIsHigherInTheLast5Minutes(5.0)
                ->failWhenLoadIsHigherInTheLast15Minutes(2.5),
            ScheduleCheck::new()
                ->heartbeatMaxAgeInMinutes(2),
            QueueCheck::new(),
            // HorizonCheck::new(),
            // DatabaseSizeCheck::new()
            //     ->failWhenSizeAboveGb(errorThresholdGb: 5.0),
            // DatabaseTableSizeCheck::new()
            //     ->table('monitor_histories', maxSizeInMb: 1_000)
            //     ->table('monitor_uptime_dailies', maxSizeInMb: 5_00)
            //     ->table('health_check_result_history_items', maxSizeInMb: 5_00),
        ]);
    }
}
