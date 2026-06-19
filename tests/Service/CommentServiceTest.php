<?php

namespace App\Tests\Service;

use App\Entity\Notification;
use App\Entity\Recipe;
use App\Entity\RecipeComment;
use App\Entity\User;
use App\Repository\RecipeCommentRepository;
use App\Service\CommentService;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class CommentServiceTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private RecipeCommentRepository $commentRepository;
    private NotificationService $notificationService;
    private CommentService $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->commentRepository = $this->createMock(RecipeCommentRepository::class);
        $this->notificationService = $this->createMock(NotificationService::class);

        $this->service = new CommentService(
            $this->commentRepository,
            $this->notificationService,
            $this->entityManager,
        );
    }

    public function testAddThrowsWhenBodyExceedsMaxLength(): void
    {
        $this->entityManager->expects($this->never())->method('persist');

        $this->expectException(\InvalidArgumentException::class);

        $this->service->add(new User(), $this->recipeWithAuthor(new User()), str_repeat('a', 2001));
    }

    public function testAddAllowsBodyAtMaxLength(): void
    {
        $author = $this->userWithId(1);
        $commenter = $this->userWithId(2);

        $this->entityManager->expects($this->once())->method('persist')->with($this->isInstanceOf(RecipeComment::class));
        $this->entityManager->expects($this->once())->method('flush');

        $this->service->add($commenter, $this->recipeWithAuthor($author), str_repeat('a', 2000));
    }

    public function testAddNotifiesRecipeAuthorWhenCommenterIsDifferentUser(): void
    {
        $author = $this->userWithId(1);
        $commenter = $this->userWithId(2);
        $recipe = $this->recipeWithAuthor($author);

        $this->notificationService->expects($this->once())->method('notify')
            ->with($author, $commenter, $recipe, Notification::TYPE_COMMENT);

        $this->service->add($commenter, $recipe, 'Looks delicious!');
    }

    public function testAddDoesNotNotifyWhenCommentingOnOwnRecipe(): void
    {
        $author = $this->userWithId(1);
        $recipe = $this->recipeWithAuthor($author);

        $this->notificationService->expects($this->never())->method('notify');

        $this->service->add($author, $recipe, 'Note to self.');
    }

    public function testSoftDeleteThrowsWhenCommentNotFound(): void
    {
        $this->commentRepository->method('find')->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Comment not found.');

        $this->service->softDelete(new User(), 1, 1);
    }

    public function testSoftDeleteThrowsWhenCommentBelongsToDifferentRecipe(): void
    {
        $author = $this->userWithId(1);
        $comment = $this->commentOn($this->recipeWithIdAndAuthor(5, $author), $author);
        $this->commentRepository->method('find')->willReturn($comment);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Comment not found.');

        $this->service->softDelete($author, 1, 999);
    }

    public function testSoftDeleteThrowsWhenRequestedByNonAuthor(): void
    {
        $author = $this->userWithId(1);
        $intruder = $this->userWithId(2);
        $recipe = $this->recipeWithIdAndAuthor(5, $author);
        $comment = $this->commentOn($recipe, $author);
        $this->commentRepository->method('find')->willReturn($comment);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Comment not found.');

        $this->service->softDelete($intruder, 1, 5);
    }

    public function testSoftDeleteMarksCommentDeletedWhenAuthorAndRecipeMatch(): void
    {
        $author = $this->userWithId(1);
        $recipe = $this->recipeWithIdAndAuthor(5, $author);
        $comment = $this->commentOn($recipe, $author);
        $this->commentRepository->method('find')->willReturn($comment);

        $this->entityManager->expects($this->once())->method('flush');

        $this->service->softDelete($author, 1, 5);

        self::assertTrue($comment->isDeleted());
    }

    private function userWithId(int $id): User
    {
        $user = new User();
        (new \ReflectionProperty(User::class, 'id'))->setValue($user, $id);

        return $user;
    }

    private function recipeWithAuthor(User $author): Recipe
    {
        $recipe = new Recipe();
        $recipe->setAuthor($author);

        return $recipe;
    }

    private function recipeWithIdAndAuthor(int $id, User $author): Recipe
    {
        $recipe = $this->recipeWithAuthor($author);
        (new \ReflectionProperty(Recipe::class, 'id'))->setValue($recipe, $id);

        return $recipe;
    }

    private function commentOn(Recipe $recipe, User $author): RecipeComment
    {
        $comment = new RecipeComment();
        $comment->setRecipe($recipe)->setAuthor($author)->setBody('A comment');

        return $comment;
    }
}
