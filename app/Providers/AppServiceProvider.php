<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

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
        $this->configureRateLimiting();

        // The reset link in the email points at the frontend (SPA), not the API.
        ResetPassword::createUrlUsing(function (CanResetPassword $notifiable, string $token) {
            $base = rtrim((string) config('app.frontend_url', config('app.url')), '/');

            return $base.'/password-reset?token='.$token.'&email='.urlencode($notifiable->getEmailForPasswordReset());
        });
    }

    /**
     * Named request rate limiters.
     */
    private function configureRateLimiting(): void
    {
        // Strict limit on login/register to slow down password brute-forcing.
        // Keyed by email + IP so the limit is targeted rather than global.
        RateLimiter::for('auth', function (Request $request) {
            $key = (string) $request->input('email').'|'.$request->ip();

            return Limit::perMinute(5)->by($key);
        });

        // General limit for the rest of the API: per user (or per IP for guests).
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
