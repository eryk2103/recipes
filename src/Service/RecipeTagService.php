<?php

namespace App\Service;

use App\Entity\RecipeTag;
use App\Repository\RecipeTagRepository;

class RecipeTagService
{
    public function __construct(
        private RecipeTagRepository $recipeTagRepository,
    ) {
    }

    /** @return RecipeTag[] */
    public function search(string $query): array
    {
        return $this->recipeTagRepository->search($query);
    }

    /** @return RecipeTag[] */
    public function findAll(): array
    {
        return $this->recipeTagRepository->findBy([], ['name' => 'ASC']);
    }
}
