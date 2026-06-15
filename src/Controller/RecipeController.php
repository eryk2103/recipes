<?php

namespace App\Controller;

use App\Dto\CreateRecipeDto;
use App\Dto\CreateRecipeIngredientDto;
use App\Dto\CreateRecipeStepDto;
use App\Form\RecipeType;
use App\Service\RecipeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class RecipeController extends AbstractController
{
    #[Route('/', name: 'app_recipe_discover', methods: ['GET'])]
    public function discover(): Response
    {
        return $this->render('recipe/discover.html.twig', []);
    }

    #[Route('/recipes', name: 'app_recipe_index', methods: ['GET'])]
    public function index(#[CurrentUser] $user, RecipeService $recipeService): Response
    {
        $recipes = $recipeService->getByAuthor($user);
        return $this->render('recipe/index.html.twig', [
            'recipes' => $recipes,
        ]);
    }

    #[Route('/recipes/new', name: 'app_recipe_new', methods: ['GET', 'POST'])]
    public function new(#[CurrentUser] $user, Request $request, RecipeService $recipeService): Response
    {
        $dto = new CreateRecipeDto();
        $dto->recipeIngredients[] = new CreateRecipeIngredientDto();
        $dto->steps[] = new CreateRecipeStepDto();

        $form = $this->createForm(RecipeType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $recipeService->create($dto, $user);

            return $this->redirectToRoute('app_recipe_index');
        }

        return $this->render('recipe/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/recipes/{id}', name: 'app_recipe_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id, #[CurrentUser] $user, RecipeService $recipeService): Response
    {
        $recipe = $recipeService->find($id);

        if (!$recipe || $recipe->getAuthor() !== $user) {
            throw $this->createNotFoundException();
        }

        return $this->render('recipe/show.html.twig', [
            'recipe' => $recipe,
        ]);
    }

    #[Route('/recipes/{id}/edit', name: 'app_recipe_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(int $id, #[CurrentUser] $user, Request $request, RecipeService $recipeService): Response
    {
        $recipe = $recipeService->find($id);

        if (!$recipe || $recipe->getAuthor() !== $user) {
            throw $this->createNotFoundException();
        }

        $dto = $recipeService->toDto($recipe);

        $form = $this->createForm(RecipeType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $recipeService->update($recipe, $dto);

            return $this->redirectToRoute('app_recipe_index');
        }

        return $this->render('recipe/edit.html.twig', [
            'form' => $form,
        ]);
    }
}
