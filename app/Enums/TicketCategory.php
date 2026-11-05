<?php

namespace App\Enums;

enum TicketCategory: string
{
    case REGULAR = 'regular';
    case VIP = 'vip';
    case OTHER = 'other';
}
