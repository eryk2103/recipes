<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\Recipe;
use App\Entity\User;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;

class NotificationService
{
    public function __construct(
        private NotificationRepository $notificationRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function notify(User $recipient, User $actor, Recipe $recipe, string $type): void
    {
        $notification = new Notification();
        $notification->setRecipient($recipient)->setActor($actor)->setRecipe($recipe)->setType($type);
        $this->entityManager->persist($notification);
    }

    public function markRead(User $user, int $id): Recipe
    {
        $notification = $this->notificationRepository->find($id);

        if (!$notification || $notification->getRecipient() !== $user) {
            throw new \RuntimeException('Notification not found.');
        }

        $notification->markRead();
        $this->entityManager->flush();

        return $notification->getRecipe();
    }
}
