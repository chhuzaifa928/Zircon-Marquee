<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        // Super admin (Khan Group) bypasses every authorization check.
        Gate::before(function (User $user, string $ability) {
            return $user->isSuperAdmin() ? true : null;
        });

        // Enforce HTTPS for all generated URLs in production (SRS §17).
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Strong password policy for all password fields (min 12, mixed case,
        // numbers, symbols, and not found in known breaches when online).
        Password::defaults(function () {
            $rule = Password::min(12)->mixedCase()->numbers()->symbols();

            return $this->app->isProduction()
                ? $rule->uncompromised()
                : $rule;
        });

        $this->logAuthenticationEvents();
    }

    /**
     * Record authentication events for the security audit trail (SRS §17).
     */
    private function logAuthenticationEvents(): void
    {
        Event::listen(Login::class, function (Login $event): void {
            Log::info('auth.login', [
                'user_id' => $event->user->getAuthIdentifier(),
                'email' => $event->user->email ?? null,
                'ip' => request()->ip(),
            ]);
        });

        Event::listen(Logout::class, function (Logout $event): void {
            Log::info('auth.logout', [
                'user_id' => $event->user?->getAuthIdentifier(),
                'ip' => request()->ip(),
            ]);
        });

        Event::listen(Failed::class, function (Failed $event): void {
            Log::warning('auth.failed', [
                'email' => $event->credentials['email'] ?? null,
                'ip' => request()->ip(),
            ]);
        });

        Event::listen(Lockout::class, function (Lockout $event): void {
            Log::warning('auth.lockout', [
                'ip' => $event->request->ip(),
            ]);
        });
    }
}
