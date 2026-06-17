<?php

namespace App\Controller;

use App\Dto\CreateRecipeDto;
use App\Dto\CreateRecipeIngredientDto;
use App\Dto\CreateRecipeStepDto;
use App\Entity\RecipeComment;
use App\Form\RecipeType;
use App\Repository\RecipeCommentRepository;
use App\Repository\RecipeRepository;
use App\Service\FileUploadService;
use App\Service\RecipeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class RecipeController extends AbstractController
{
    #[Route('/', name: 'app_recipe_discover', methods: ['GET'])]
    public function discover(RecipeRepository $recipeRepository): Response
    {
        return $this->render('recipe/discover.html.twig', [
            'recipes' => $recipeRepository->findAllPublic(),
        ]);
    }

    #[Route('/recipes', name: 'app_recipe_index', methods: ['GET'])]
    public function index(#[CurrentUser] $user, RecipeService $recipeService): Response
    {
        $recipes = array_merge(
            $recipeService->getByAuthor($user),
            $recipeService->getSavedByUser($user),
        );

        return $this->render('recipe/index.html.twig', [
            'recipes' => $recipes,
        ]);
    }

    #[Route('/recipes/new', name: 'app_recipe_new', methods: ['GET', 'POST'])]
    public function new(#[CurrentUser] $user, Request $request, RecipeService $recipeService, FileUploadService $fileUploadService): Response
    {
        $dto = new CreateRecipeDto();
        $dto->recipeIngredients[] = new CreateRecipeIngredientDto();
        $dto->steps[] = new CreateRecipeStepDto();

        $form = $this->createForm(RecipeType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('photo')->getData();
            if ($file) {
                $newFileName = $fileUploadService->upload($file);
                $dto->photo = $newFileName;
            }

            $recipeService->create($dto, $user);

            return $this->redirectToRoute('app_recipe_index');
        }

        return $this->render('recipe/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/recipes/{id}', name: 'app_recipe_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id, RecipeService $recipeService): Response
    {
        $recipe = $recipeService->find($id);
        $user = $this->getUser();

        if (!$recipe || (!$recipe->isPublic() && $recipe->getAuthor() !== $user)) {
            throw $this->createNotFoundException();
        }

        return $this->render('recipe/show.html.twig', [
            'recipe' => $recipe,
            'isOwner' => $recipe->getAuthor() === $user,
            'isSaved' => $recipeService->isSaved($user, $recipe),
        ]);
    }

    #[Route('/recipes/{id}/comment', name: 'app_recipe_comment', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function comment(int $id, #[CurrentUser] $user, Request $request, RecipeService $recipeService, EntityManagerInterface $em): Response
    {
        $recipe = $recipeService->find($id);

        if (!$recipe || (!$recipe->isPublic() && $recipe->getAuthor() !== $user)) {
            throw $this->createNotFoundException();
        }

        $body = trim($request->request->getString('body'));
        if ($body !== '') {
            $comment = new RecipeComment();
            $comment->setRecipe($recipe)->setAuthor($user)->setBody($body);
            $em->persist($comment);
            $em->flush();
        }

        return $this->redirectToRoute('app_recipe_show', ['id' => $id]);
    }

    #[Route('/recipes/{id}/comment/{commentId}/delete', name: 'app_recipe_comment_delete', methods: ['POST'], requirements: ['id' => '\d+', 'commentId' => '\d+'])]
    public function deleteComment(int $id, int $commentId, #[CurrentUser] $user, RecipeCommentRepository $commentRepository, EntityManagerInterface $em): Response
    {
        $comment = $commentRepository->find($commentId);

        if (!$comment || $comment->getRecipe()->getId() !== $id || $comment->getAuthor() !== $user) {
            throw $this->createNotFoundException();
        }

        $comment->softDelete();
        $em->flush();

        return $this->redirectToRoute('app_recipe_show', ['id' => $id]);
    }

    #[Route('/recipes/{id}/edit', name: 'app_recipe_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(int $id, #[CurrentUser] $user, Request $request, RecipeService $recipeService, FileUploadService $fileUploadService): Response
    {
        $recipe = $recipeService->find($id);

        if (!$recipe || $recipe->getAuthor() !== $user) {
            throw $this->createNotFoundException();
        }

        $dto = $recipeService->toDto($recipe);

        $form = $this->createForm(RecipeType::class, $dto);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('photo')->getData();
            if ($file) {
                $newFileName = $fileUploadService->upload($file);
                $dto->photo = $newFileName;
            }

            $recipeService->update($recipe, $dto);

            return $this->redirectToRoute('app_recipe_index');
        }

        return $this->render('recipe/edit.html.twig', [
            'form' => $form,
            'recipe' => $recipe,
        ]);
    }
}
