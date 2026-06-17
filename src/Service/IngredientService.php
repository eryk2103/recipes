<?php

namespace App\Service;

use App\Entity\Ingredient;
use App\Repository\IngredientRepository;

class IngredientService
{
    public function __construct(
        private IngredientRepository $ingredientRepository,
    ) {
    }

    /** @return Ingredient[] */
    public function search(string $query): array
    {
        return $this->ingredientRepository->search($query);
    }

    /** @return Ingredient[] */
    public function findAll(): array
    {
        return $this->ingredientRepository->findBy([], ['name' => 'ASC']);
    }
}
