<?php

namespace App\Enums;

enum TestType: string
{
    case Public = 'public';
    case Company = 'company';
    case All = 'all';
}

