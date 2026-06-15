<?php

namespace App\Service;

use App\Dto\CreateRecipeDto;
use App\Entity\Ingredient;
use App\Entity\Recipe;
use App\Entity\RecipeIngredient;
use App\Entity\RecipeStep;
use App\Entity\User;
use App\Repository\IngredientRepository;
use App\Repository\RecipeRepository;
use Doctrine\ORM\EntityManagerInterface;

class RecipeService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RecipeRepository $recipeRepository,
        private IngredientRepository $ingredientRepository,
    ) {
    }

    public function find(int $id): ?Recipe
    {
        return $this->recipeRepository->find($id);
    }

    /**
     * @return Recipe[]
     */
    public function findAll(): array
    {
        return $this->recipeRepository->findAll();
    }

    public function create(CreateRecipeDto $dto, User $author): Recipe
    {
        $recipe = new Recipe();
        $recipe->setAuthor($author);

        $this->applyDto($recipe, $dto);

        $this->entityManager->persist($recipe);
        $this->entityManager->flush();

        return $recipe;
    }

    public function update(Recipe $recipe, CreateRecipeDto $dto): Recipe
    {
        foreach ($recipe->getRecipeIngredients()->toArray() as $recipeIngredient) {
            $recipe->removeRecipeIngredient($recipeIngredient);
        }

        foreach ($recipe->getSteps()->toArray() as $step) {
            $recipe->removeStep($step);
        }

        $this->applyDto($recipe, $dto);

        $this->entityManager->flush();

        return $recipe;
    }

    public function delete(Recipe $recipe): void
    {
        $this->entityManager->remove($recipe);
        $this->entityManager->flush();
    }

    private function applyDto(Recipe $recipe, CreateRecipeDto $dto): void
    {
        $recipe->setTitle($dto->title);
        $recipe->setNotes($dto->notes);
        $recipe->setServings($dto->servings);
        $recipe->setCookTimeMinutes($dto->cookTimeMinutes);

        foreach ($dto->recipeIngredients as $recipeIngredientDto) {
            $ingredient = $this->ingredientRepository->findOneBy(['name' => $recipeIngredientDto->name])
                ?? new Ingredient()->setName($recipeIngredientDto->name);

            $recipeIngredient = new RecipeIngredient();
            $recipeIngredient->setIngredient($ingredient);
            $recipeIngredient->setAmount($recipeIngredientDto->amount);
            $recipeIngredient->setUnit($recipeIngredientDto->unit);
            $recipeIngredient->setPosition($recipeIngredientDto->position);

            $recipe->addRecipeIngredient($recipeIngredient);
        }

        foreach ($dto->steps as $stepDto) {
            $step = new RecipeStep();
            $step->setInstruction($stepDto->instruction);
            $step->setPosition($stepDto->position);

            $recipe->addStep($step);
        }
    }
}
