<?php

namespace App\Enums;

enum EmployeeStatus: string
{
    case ACTIVE = 'Active';

    case INACTIVE = 'Inactive';

    case ON_LEAVE = 'On Leave';

    case TERMINATED = 'Terminated';
}