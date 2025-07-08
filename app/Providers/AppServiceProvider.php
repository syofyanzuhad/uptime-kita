<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Event;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Opcodes\LogViewer\Facades\LogViewer;
use App\Listeners\SendCustomMonitorNotification;
use Spatie\UptimeMonitor\Events\UptimeCheckFailed;
use Spatie\UptimeMonitor\Events\UptimeCheckRecovered;

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
    }
}
