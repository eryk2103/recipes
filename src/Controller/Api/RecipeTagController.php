<?php

namespace App\Controller\Api;

use App\Repository\RecipeTagRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/recipe-tags', name: 'api_recipe_tag_')]
class RecipeTagController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, RecipeTagRepository $recipeTagRepository): JsonResponse
    {
        $search = $request->query->getString('search');

        $tags = $search
            ? $recipeTagRepository->search($search)
            : $recipeTagRepository->findBy([], ['name' => 'ASC']);

        return $this->json(array_map(fn($t) => [
            'id' => $t->getId(),
            'name' => $t->getName(),
        ], $tags));
    }
}
