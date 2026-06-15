<?php

namespace App\Dto;

class CreateRecipeDto
{
    public ?string $title = null;

    public ?string $notes = null;

    public ?int $servings = null;

    public ?int $cookTimeMinutes = null;

    public bool $isPublic = false;

    /**
     * @var CreateRecipeIngredientDto[]
     */
    public array $recipeIngredients = [];

    /**
     * @var CreateRecipeStepDto[]
     */
    public array $steps = [];

    /**
     * @var string[]
     */
    public array $tags = [];
}
