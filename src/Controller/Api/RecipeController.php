<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Service\RecipeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/recipes', name: 'api_recipe_')]
class RecipeController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(#[CurrentUser] User $user, Request $request, RecipeService $recipeService): JsonResponse
    {
        $search = $request->query->getString('search');

        $recipes = $search
            ? $recipeService->search($user, $search)
            : $recipeService->getByAuthor($user);

        return $this->json(array_map(fn($r) => [
            'id' => $r->getId(),
            'title' => $r->getTitle(),
            'isPublic' => $r->isPublic(),
        ], $recipes));
    }

    #[Route('/public', name: 'public', methods: ['GET'])]
    public function public(Request $request, RecipeService $recipeService): JsonResponse
    {
        $search = $request->query->getString('search');

        $recipes = $search
            ? $recipeService->searchPublic($search)
            : $recipeService->findAllPublic();

        return $this->json(array_map(fn($r) => [
            'id' => $r->getId(),
            'title' => $r->getTitle(),
        ], $recipes));
    }

    #[Route('/{id}/save', name: 'save', methods: ['POST'])]
    public function save(int $id, RecipeService $recipeService, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        if (!$this->isCsrfTokenValid('recipe-save', $request->headers->get('X-CSRF-TOKEN'))) {
            return $this->json(null, 403);
        }

        $recipe = $recipeService->find($id);
        if ($recipe === null) {
            throw $this->createNotFoundException();
        }

        try {
            $recipeService->save($user, $recipe);
        } catch (\Exception) {
            return $this->json(null, 400);
        }

        return $this->json(null, 204);
    }

    #[Route('/{id}/unsave', name: 'unsave', methods: ['POST'])]
    public function unsave(int $id, RecipeService $recipeService, Request $request, #[CurrentUser] User $user): JsonResponse
    {
        if (!$this->isCsrfTokenValid('recipe-save', $request->headers->get('X-CSRF-TOKEN'))) {
            return $this->json(null, 403);
        }

        $recipe = $recipeService->find($id);
        if ($recipe === null) {
            throw $this->createNotFoundException();
        }

        try {
            $recipeService->unsave($user, $recipe);
        } catch (\Exception) {
            return $this->json(null, 400);
        }

        return $this->json(null, 204);
    }
}
