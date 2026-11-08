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
    }

    public function boot(): void
    {
        Gate::policy(Event::class, EventPolicy::class);
        Gate::policy(TicketType::class, TicketTypePolicy::class);
        Gate::policy(Booking::class, BookingPolicy::class);
    }
}
