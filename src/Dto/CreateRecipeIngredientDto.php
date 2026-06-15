<?php

namespace App\Dto;

use App\Enum\Unit;

class CreateRecipeIngredientDto
{
    public ?int $amount = null;

    public ?Unit $unit = null;

    public ?string $name = null;

    public ?int $position = null;
}
