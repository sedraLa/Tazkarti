<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\Event;
use App\Models\TicketType;
use App\Policies\BookingPolicy;
use App\Policies\EventPolicy;
use App\Policies\TicketTypePolicy;
use App\Services\Email\EmailProviderInterface;
use App\Services\Email\LogEmailProvider;
use App\Services\Sms\LogSmsProvider;
use App\Services\Sms\SmsProviderInterface;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(EmailProviderInterface::class, function () {
            $provider = config('services.email.provider', 'log');

            return match ($provider) {
                'log', 'fake' => new LogEmailProvider(),
                default => new LogEmailProvider(),
            };
        });

        $this->app->bind(SmsProviderInterface::class, function () {
            $provider = config('services.sms.provider', 'log');

            return match ($provider) {
                'log', 'fake' => new LogSmsProvider(),
                default => new LogSmsProvider(),
            };
        });
    }

    public function boot(): void
    {
        Gate::policy(Event::class, EventPolicy::class);
        Gate::policy(TicketType::class, TicketTypePolicy::class);
        Gate::policy(Booking::class, BookingPolicy::class);
    }
}
