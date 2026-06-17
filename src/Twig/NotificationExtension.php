<?php

namespace App\Twig;

use App\Repository\NotificationRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class NotificationExtension extends AbstractExtension
{
    private ?array $cache = null;

    public function __construct(
        private NotificationRepository $notificationRepository,
        private Security $security,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('notifications', $this->getNotifications(...)),
            new TwigFunction('unread_notification_count', $this->getUnreadCount(...)),
        ];
    }

    public function getNotifications(): array
    {
        return $this->load();
    }

    public function getUnreadCount(): int
    {
        return count(array_filter($this->load(), fn($n) => !$n->isRead()));
    }

    private function load(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        $user = $this->security->getUser();
        $this->cache = $user ? $this->notificationRepository->findForUser($user) : [];

        return $this->cache;
    }
}
