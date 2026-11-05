<?php

namespace App\Enums;

enum TicketStatus: string
{
    case VALID = 'valid';
    case CANCELLED = 'cancelled';
    case USED = 'used';
}
