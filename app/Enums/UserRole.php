<?php

namespace App\Enums;

enum UserRole: string
{
    case ORGANIZER = 'organizer';
    case CUSTOMER = 'customer';
    case ADMIN = 'admin';
}
