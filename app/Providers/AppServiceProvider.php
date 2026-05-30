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

        // Ссылка в письме сброса пароля ведёт на фронтенд (SPA), а не на бэкенд.
        ResetPassword::createUrlUsing(function (CanResetPassword $notifiable, string $token) {
            $base = rtrim((string) config('app.frontend_url', config('app.url')), '/');

            return $base.'/password-reset?token='.$token.'&email='.urlencode($notifiable->getEmailForPasswordReset());
        });
    }

    /**
     * Именованные ограничители частоты запросов.
     */
    private function configureRateLimiting(): void
    {
        // Строгий лимит на вход/регистрацию — защита от перебора паролей.
        // Ключ: email + IP, чтобы лимит был точечным.
        RateLimiter::for('auth', function (Request $request) {
            $key = (string) $request->input('email').'|'.$request->ip();

            return Limit::perMinute(5)->by($key);
        });

        // Общий лимит на остальной API: на пользователя (или IP для гостя).
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
