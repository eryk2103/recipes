<?php

namespace App\Tests\Service;

use App\Entity\Notification;
use App\Entity\Recipe;
use App\Entity\User;
use App\Repository\NotificationRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class NotificationServiceTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private NotificationRepository $notificationRepository;
    private NotificationService $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->notificationRepository = $this->createMock(NotificationRepository::class);

        $this->service = new NotificationService(
            $this->notificationRepository,
            $this->entityManager,
        );
    }

    public function testMarkReadThrowsWhenNotificationNotFound(): void
    {
        $this->notificationRepository->method('find')->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Notification not found.');

        $this->service->markRead(new User(), 1);
    }

    public function testMarkReadThrowsWhenRecipientDoesNotMatch(): void
    {
        $recipient = $this->userWithId(1);
        $other = $this->userWithId(2);

        $notification = new Notification();
        $notification->setRecipient($recipient)->setActor($other)->setRecipe(new Recipe())->setType(Notification::TYPE_SAVE);

        $this->notificationRepository->method('find')->willReturn($notification);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Notification not found.');

        $this->service->markRead($other, 1);
    }

    public function testMarkReadMarksNotificationAsReadAndReturnsItsRecipe(): void
    {
        $recipient = $this->userWithId(1);
        $recipe = new Recipe();

        $notification = new Notification();
        $notification->setRecipient($recipient)->setActor($this->userWithId(2))->setRecipe($recipe)->setType(Notification::TYPE_COMMENT);

        $this->notificationRepository->method('find')->willReturn($notification);
        $this->entityManager->expects($this->once())->method('flush');

        $result = $this->service->markRead($recipient, 1);

        self::assertTrue($notification->isRead());
        self::assertSame($recipe, $result);
    }

    private function userWithId(int $id): User
    {
        $user = new User();
        (new \ReflectionProperty(User::class, 'id'))->setValue($user, $id);

        return $user;
    }
}
