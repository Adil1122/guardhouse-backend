<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;

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
        // Rate limit for login route: 10 hits per minute
        RateLimiter::for('login', function (Request $request) {
            if (env('APP_URL') === 'http://localhost') {
                return Limit::none(); // Disable rate limiting
            }
            return Limit::perMinute(3)->by($request->ip());
        });
        
        RateLimiter::for('signup', function (Request $request) {
            if (env('APP_URL') === 'http://localhost') {
                return Limit::none(); // Disable rate limiting
            }
            return Limit::perMinute(3)->by($request->ip());
        });
    }
}
