<?php

namespace App\Controller\Api;

use App\Entity\Notification;
use App\Repository\RecipeRepository;
use App\Service\RecipeService;
use Doctrine\ORM\EntityManagerInterface;
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

    #[Route('/public', name: 'public', methods: ['GET'])]
    public function public(Request $request, RecipeRepository $recipeRepository): JsonResponse
    {
        $search = $request->query->getString('search');

        $recipes = $search
            ? $recipeRepository->searchPublic($search)
            : $recipeRepository->findAllPublic();

        return $this->json(array_map(fn($r) => [
            'id' => $r->getId(),
            'title' => $r->getTitle(),
        ], $recipes));
    }

    #[Route('/{id}/save', name: 'save', methods: ['GET'])]
    public function save(int $id, RecipeService $recipeService, EntityManagerInterface $em, #[CurrentUser] $user): JsonResponse
    {
        $recipe = $recipeService->find($id);
        if ($recipe === null) {
            throw $this->createNotFoundException();
        }

        try {
            $recipeService->save($user, $recipe);
        } catch (\Exception $e) {
            return $this->json(null, 400);
        }

        $notification = new Notification();
        $notification->setRecipient($recipe->getAuthor())->setActor($user)->setRecipe($recipe)->setType(Notification::TYPE_SAVE);
        $em->persist($notification);
        $em->flush();

        return $this->json(null, 204);
    }

    #[Route('/{id}/unsave', name: 'unsave', methods: ['GET'])]
    public function unsave(int $id, RecipeService $recipeService, #[CurrentUser] $user): JsonResponse
    {
        $recipe = $recipeService->find($id);
        if($recipe === null)
        {
            throw $this->createNotFoundException();
        }

        try{
            $recipeService->unsave($user, $recipe);
        }
        catch(\Exception $e)
        {
            return $this->json(null, 400);
        }

        return $this->json(null, 204);
    }
}
