<?php

namespace App\Controller\Api;

use App\Service\IngredientService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/ingredients', name: 'api_ingredient_')]
class IngredientController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, IngredientService $ingredientService): JsonResponse
    {
        $search = $request->query->getString('search');

        $ingredients = $search
            ? $ingredientService->search($search)
            : $ingredientService->findAll();

        return $this->json(array_map(fn($i) => [
            'id' => $i->getId(),
            'name' => $i->getName(),
        ], $ingredients));
    }
}
