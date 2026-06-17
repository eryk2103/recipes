<?php

namespace App\Controller;

use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class NotificationController extends AbstractController
{
    #[Route('/notifications/{id}/read', name: 'app_notification_read', methods: ['GET'])]
    public function read(int $id, #[CurrentUser] $user, NotificationRepository $notificationRepository, EntityManagerInterface $em): Response
    {
        $notification = $notificationRepository->find($id);

        if (!$notification || $notification->getRecipient() !== $user) {
            throw $this->createNotFoundException();
        }

        $notification->markRead();
        $em->flush();

        return $this->redirectToRoute('app_recipe_show', ['id' => $notification->getRecipe()->getId()]);
    }
}
