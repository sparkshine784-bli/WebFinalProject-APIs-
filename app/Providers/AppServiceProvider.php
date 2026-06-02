<?php

namespace App\Providers;

use App\Services\AttendanceService;
use App\Services\AttendanceServiceInterface;
use App\Services\AuthService;
use App\Services\AuthServiceInterface;
use App\Services\GradeService;
use App\Services\GradeServiceInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(AuthServiceInterface::class, AuthService::class);
        $this->app->bind(GradeServiceInterface::class, GradeService::class);
        $this->app->bind(AttendanceServiceInterface::class, AttendanceService::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
