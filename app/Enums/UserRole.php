<?php

namespace App\Enums;

enum UserRole: string
{
    case Driver = 'driver';
    case Supervisor = 'supervisor';
}
