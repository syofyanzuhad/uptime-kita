<?php

namespace App\Providers;

use App\Listeners\SendCustomMonitorNotification;
use Illuminate\Database\Eloquent\Model;
use Opcodes\LogViewer\Facades\LogViewer;
use Illuminate\Support\Facades\{Event, URL};
use Illuminate\Support\ServiceProvider;
use Spatie\Health\Facades\Health;
use Spatie\CpuLoadHealthCheck\CpuLoadCheck;
use Spatie\Health\Checks\Checks\{CacheCheck, DatabaseCheck, OptimizedAppCheck, QueueCheck, RedisCheck, RedisMemoryUsageCheck, ScheduleCheck, UsedDiskSpaceCheck};
use Spatie\UptimeMonitor\Events\{UptimeCheckFailed, UptimeCheckRecovered};


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
                ->failWhenLoadIsHigherInTheLast5Minutes(2.0)
                ->failWhenLoadIsHigherInTheLast5Minutes(5.0)
                ->failWhenLoadIsHigherInTheLast15Minutes(3.0),
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
