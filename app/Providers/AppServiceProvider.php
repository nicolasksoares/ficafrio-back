<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use App\Enums\UserType;
use App\Contracts\PaymentGatewayInterface;
use App\Services\PaymentGatewayService;
use App\Services\StripeGatewayService;
use App\Models\Payment;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $gateway = config('payment.gateway');
        $implementation = ($gateway === 'stripe') ? StripeGatewayService::class : PaymentGatewayService::class;
        $this->app->bind(PaymentGatewayInterface::class, $implementation);
    }

    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());

        Gate::define('manage-quote', function ($user, $quote) {
            return $user->id === $quote->storageRequest->company_id || 
                   $user->id === $quote->space->company_id;
        });

        // Registra PaymentPolicy
        Gate::policy(Payment::class, \App\Policies\PaymentPolicy::class);

        Gate::before(function ($user, $ability) {
            return $user->type === UserType::Admin ? true : null; 
        });

        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return "http://localhost:5173/reset-password?token={$token}&email={$notifiable->getEmailForPasswordReset()}";
        });

        DB::listen(function ($query) {
            if (str_contains($query->sql, 'personal_access_tokens')) {
                return;
            }

            Log::info('💾 [SQL]', [
                'sql' => $query->sql,
                'bindings' => $query->bindings,
                'time' => $query->time . 'ms',
            ]);
        });
    }
}