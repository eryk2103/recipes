<?php

namespace App\Message;

class CalculateRecipeNutritionMessage
{
    public function __construct(
        public readonly int $recipeId,
    ) {}
}
