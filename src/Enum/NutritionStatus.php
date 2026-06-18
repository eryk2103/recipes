<?php

namespace App\Enum;

enum NutritionStatus : string
{
    case PENDING = 'PENDING';
    case DONE = 'DONE';
    case FAILED = 'FAILED';
}
