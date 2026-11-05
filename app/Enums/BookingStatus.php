<?php

namespace App\Enums;

enum BookingStatus: string
{
    case HELD = 'held';
    case PAID = 'paid';
    case EXPIRED = 'expired';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';
}
