<?php

namespace App\Providers;

use App\Models\Monitor;
use App\Models\StatusPage;
use App\Models\User;
use App\Policies\MonitorPolicy;
use App\Policies\StatusPagePolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Monitor::class => MonitorPolicy::class,
        StatusPage::class => StatusPagePolicy::class,
        User::class => UserPolicy::class,
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
