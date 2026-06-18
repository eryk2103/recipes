<?php

namespace App\Service\Interface;

use App\Entity\Recipe;

interface NutritionServiceInterface
{
    function estimate(Recipe $recipe): array;
}
