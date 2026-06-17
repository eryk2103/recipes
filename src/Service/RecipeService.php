<?php

namespace App\Service;

use App\Dto\CreateRecipeDto;
use App\Dto\CreateRecipeIngredientDto;
use App\Dto\CreateRecipeStepDto;
use App\Entity\Ingredient;
use App\Entity\Recipe;
use App\Entity\RecipeIngredient;
use App\Entity\Notification;
use App\Entity\RecipeSaved;
use App\Entity\RecipeStep;
use App\Entity\RecipeTag;
use App\Entity\User;
use App\Repository\IngredientRepository;
use App\Repository\RecipeRepository;
use App\Repository\RecipeSavedRepository;
use App\Repository\RecipeTagRepository;
use Doctrine\ORM\EntityManagerInterface;

class RecipeService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RecipeRepository       $recipeRepository,
        private IngredientRepository   $ingredientRepository,
        private RecipeTagRepository    $recipeTagRepository,
        private RecipeSavedRepository  $recipeSavedRepository,
        private NotificationService    $notificationService,
    ) {
    }

    public function find(int $id): ?Recipe
    {
        return $this->recipeRepository->find($id);
    }

    /** @return Recipe[] */
    public function getByAuthor(User $user): array
    {
        return $this->recipeRepository->findBy(['author' => $user], ['title' => 'ASC']);
    }

    public function isSaved(?User $user, Recipe $recipe): bool
    {
        if ($user === null) {
            return false;
        }

        return $this->recipeSavedRepository->findOneBy(['recipe' => $recipe, 'acount' => $user]) !== null;
    }

    public function save(User $user, Recipe $recipe): void
    {
        $savedRecipe = $this->recipeSavedRepository->findOneBy(['recipe' => $recipe, 'acount' => $user]);
        if ($savedRecipe !== null) {
            throw new \RuntimeException('Recipe already saved');
        }

        if ($recipe->getAuthor()->getId() === $user->getId()) {
            throw new \RuntimeException('Cannot save your own recipe.');
        }

        $newSavedRecipe = new RecipeSaved()
            ->setRecipe($recipe)
            ->setAcount($user);

        $this->entityManager->persist($newSavedRecipe);

        $this->notificationService->notify($recipe->getAuthor(), $user, $recipe, Notification::TYPE_SAVE);

        $this->entityManager->flush();
    }

    public function unsave(User $user, Recipe $recipe): void
    {
        $savedRecipe = $this->recipeSavedRepository->findOneBy(['recipe' => $recipe, 'acount' => $user]);
        if ($savedRecipe === null) {
            throw new \RuntimeException('Recipe save not found');
        }
        $this->entityManager->remove($savedRecipe);
        $this->entityManager->flush();
    }

    /** @return Recipe[] */
    public function getSavedByUser(User $user): array
    {
        return $this->recipeSavedRepository->findRecipesByUser($user);
    }

    /** @return Recipe[] */
    public function findAll(): array
    {
        return $this->recipeRepository->findAll();
    }

    /** @return Recipe[] */
    public function findAllPublic(): array
    {
        return $this->recipeRepository->findAllPublic();
    }

    /** @return Recipe[] */
    public function search(User $user, string $query): array
    {
        return $this->recipeRepository->searchByAuthor($user, $query);
    }

    /** @return Recipe[] */
    public function searchPublic(string $query): array
    {
        return $this->recipeRepository->searchPublic($query);
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

        foreach ($recipe->getTags()->toArray() as $tag) {
            $recipe->removeTag($tag);
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

    public function toDto(Recipe $recipe): CreateRecipeDto
    {
        $dto = new CreateRecipeDto();
        $dto->title = $recipe->getTitle();
        $dto->notes = $recipe->getNotes();
        $dto->servings = $recipe->getServings();
        $dto->cookTimeMinutes = $recipe->getCookTimeMinutes();
        $dto->isPublic = $recipe->isPublic() ?? false;
        $dto->photo = $recipe->getPhoto();

        foreach ($recipe->getRecipeIngredients() as $recipeIngredient) {
            $ingredientDto = new CreateRecipeIngredientDto();
            $ingredientDto->amount = $recipeIngredient->getAmount();
            $ingredientDto->unit = $recipeIngredient->getUnit();
            $ingredientDto->name = $recipeIngredient->getIngredient()->getName();
            $ingredientDto->position = $recipeIngredient->getPosition();

            $dto->recipeIngredients[] = $ingredientDto;
        }

        foreach ($recipe->getSteps() as $step) {
            $stepDto = new CreateRecipeStepDto();
            $stepDto->instruction = $step->getInstruction();
            $stepDto->position = $step->getPosition();

            $dto->steps[] = $stepDto;
        }

        foreach ($recipe->getTags() as $tag) {
            $dto->tags[] = $tag->getName();
        }

        return $dto;
    }

    private function applyDto(Recipe $recipe, CreateRecipeDto $dto): void
    {
        $recipe->setTitle($dto->title);
        $recipe->setNotes($dto->notes);
        $recipe->setServings($dto->servings);
        $recipe->setCookTimeMinutes($dto->cookTimeMinutes);
        $recipe->setIsPublic($dto->isPublic);
        $recipe->setPhoto($dto->photo);

        $ingredientNames = array_filter(array_map(fn($d) => $d->name, $dto->recipeIngredients));
        $ingredientMap = [];
        foreach ($this->ingredientRepository->findByNames($ingredientNames) as $ingredient) {
            $ingredientMap[$ingredient->getName()] = $ingredient;
        }

        foreach ($dto->recipeIngredients as $recipeIngredientDto) {
            $ingredient = $ingredientMap[$recipeIngredientDto->name] ?? new Ingredient()->setName($recipeIngredientDto->name);

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

        $tagNames = array_filter(array_map(fn($n) => trim($n ?? ''), $dto->tags));
        $tagMap = [];
        foreach ($this->recipeTagRepository->findByNames($tagNames) as $tag) {
            $tagMap[$tag->getName()] = $tag;
        }

        foreach ($dto->tags as $tagName) {
            $tagName = trim($tagName ?? '');

            if ($tagName === '') {
                continue;
            }

            $tag = $tagMap[$tagName] ?? new RecipeTag()->setName($tagName);

            $recipe->addTag($tag);
        }
    }
}
