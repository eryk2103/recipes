<?php

namespace App\Controller\Api;

use App\Repository\RecipeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/recipes', name: 'api_recipe_')]
class RecipeController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(#[CurrentUser] $user, Request $request, RecipeRepository $recipeRepository): JsonResponse
    {
        $search = $request->query->getString('search');

        $recipes = $search
            ? $recipeRepository->searchByAuthor($user, $search)
            : $recipeRepository->findBy(['author' => $user], ['title' => 'ASC']);

        return $this->json(array_map(fn($r) => [
            'id' => $r->getId(),
            'title' => $r->getTitle(),
            'isPublic' => $r->isPublic(),
        ], $recipes));
    }
}
