<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case TenantAdmin = 'tenant_admin';
    case PublicUser = 'public_user';
}

