<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class CreateRecipeDto
{
    #[Assert\NotBlank(message: 'Please enter a recipe title.')]
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

    public ?string $photo = null;
}
