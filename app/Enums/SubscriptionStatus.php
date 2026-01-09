<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
    case Pending = 'pending';
}

