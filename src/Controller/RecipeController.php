<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
    public function new(): Response
    {
        return $this->render('recipe/new.html.twig', []);
    }
}
