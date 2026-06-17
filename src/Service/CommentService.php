<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\Recipe;
use App\Entity\RecipeComment;
use App\Entity\User;
use App\Repository\RecipeCommentRepository;
use Doctrine\ORM\EntityManagerInterface;

class CommentService
{
    private const MAX_BODY_LENGTH = 2000;

    public function __construct(
        private RecipeCommentRepository $commentRepository,
        private NotificationService $notificationService,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function add(User $author, Recipe $recipe, string $body): void
    {
        if (mb_strlen($body) > self::MAX_BODY_LENGTH) {
            throw new \InvalidArgumentException('Comment exceeds maximum length of ' . self::MAX_BODY_LENGTH . ' characters.');
        }

        $comment = new RecipeComment();
        $comment->setRecipe($recipe)->setAuthor($author)->setBody($body);
        $this->entityManager->persist($comment);

        $recipeAuthor = $recipe->getAuthor();
        if ($recipeAuthor !== $author) {
            $this->notificationService->notify($recipeAuthor, $author, $recipe, Notification::TYPE_COMMENT);
        }

        $this->entityManager->flush();
    }

    public function softDelete(User $user, int $commentId, int $recipeId): void
    {
        $comment = $this->commentRepository->find($commentId);

        if (!$comment || $comment->getRecipe()->getId() !== $recipeId || $comment->getAuthor() !== $user) {
            throw new \RuntimeException('Comment not found.');
        }

        $comment->softDelete();
        $this->entityManager->flush();
    }
}
