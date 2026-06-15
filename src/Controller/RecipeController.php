<?php

namespace App\Controller;

use App\Entity\Ingredient;
use App\Entity\Recipe;
use App\Entity\RecipeIngredient;
use App\Entity\RecipeStep;
use App\Form\RecipeType;
use App\Repository\IngredientRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RecipeController extends AbstractController
{
    #[Route('/', name: 'app_recipe_discover', methods: ['GET'])]
    public function discover(): Response
    {
        return $this->render('recipe/discover.html.twig', []);
    }

    #[Route('/recipes', name: 'app_recipe_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('recipe/index.html.twig', []);
    }

    #[Route('/recipes/new', name: 'app_recipe_new', methods: ['GET', 'POST'])]
    public function new(Request $request, IngredientRepository $ingredientRepository): Response
    {
        $recipe = new Recipe();
        $recipe->addRecipeIngredient(new RecipeIngredient());
        $recipe->addStep(new RecipeStep());

        $form = $this->createForm(RecipeType::class, $recipe);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($form->get('recipeIngredients') as $recipeIngredientForm) {
                $name = $recipeIngredientForm->get('name')->getData();
                $ingredient = $ingredientRepository->findOneBy(['name' => $name])
                    ?? new Ingredient()->setName($name);

                $recipeIngredientForm->getData()->setIngredient($ingredient);
            }

        }

        return $this->render('recipe/new.html.twig', [
            'form' => $form,
        ]);
    }
}
