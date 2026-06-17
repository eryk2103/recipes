<?php

namespace App\Controller;

use App\Service\NotificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class NotificationController extends AbstractController
{
    #[Route('/notifications/{id}/read', name: 'app_notification_read', methods: ['POST'])]
    public function read(int $id, #[CurrentUser] $user, Request $request, NotificationService $notificationService): Response
    {
        if (!$this->isCsrfTokenValid('notification-read', $request->request->getString('_token'))) {
            throw $this->createAccessDeniedException();
        }

        try {
            $recipe = $notificationService->markRead($user, $id);
        } catch (\RuntimeException) {
            throw $this->createNotFoundException();
        }

        return $this->redirectToRoute('app_recipe_show', ['id' => $recipe->getId()]);
    }
}
