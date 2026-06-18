<?php

namespace App\MessageHandler;

use App\Enum\NutritionStatus;
use App\Message\CalculateRecipeNutritionMessage;
use App\Service\Interface\NutritionServiceInterface;
use App\Service\RecipeService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class CalculateRecipeNutritionHandler
{
    public function __construct(
        private readonly RecipeService $recipeService,
        private readonly NutritionServiceInterface $nutritionService,
    ) {}

    public function __invoke(CalculateRecipeNutritionMessage $message): void
    {
        $recipe = $this->recipeService->find($message->recipeId);
        if($recipe === null) {
            return;
        }

        try {
            $nutrition = $this->nutritionService->estimate($recipe);
            $this->recipeService->setNutrition($recipe, $nutrition);
        }catch (\Exception $exception){
            $this->recipeService->setNutritionStatus($recipe, NutritionStatus::FAILED);
        }
    }
}
