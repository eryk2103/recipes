<?php

namespace App\Tests\Controller;

use App\Entity\Notification;
use App\Service\CommentService;
use App\Tests\Support\IntegrationTestCase;

class NotificationControllerTest extends IntegrationTestCase
{
    public function testMarkOwnNotificationAsRead(): void
    {
        $author = $this->createUser('amelia');
        $recipe = $this->createRecipe($author, ['isPublic' => true]);

        $commenter = $this->createUser('ben');
        static::getContainer()->get(CommentService::class)->add($commenter, $recipe, 'Nice recipe!');
        $this->em->clear();

        $notification = $this->em->getRepository(Notification::class)->findOneBy(['recipient' => $author]);
        self::assertFalse($notification->isRead());

        $this->client->loginUser($author);
        $crawler = $this->client->request('GET', '/recipes');
        $form = $crawler->filter('form[action="/notifications/' . $notification->getId() . '/read"]')->form();
        $this->client->submit($form);

        self::assertResponseRedirects('/recipes/' . $recipe->getId());

        $this->em->clear();
        $notification = $this->em->getRepository(Notification::class)->find($notification->getId());
        self::assertTrue($notification->isRead());
    }

    public function testMarkAnotherUsersNotificationAsReadReturns404(): void
    {
        $author = $this->createUser('amelia');
        $recipe = $this->createRecipe($author, ['isPublic' => true]);

        $commenter = $this->createUser('ben');
        static::getContainer()->get(CommentService::class)->add($commenter, $recipe, 'Nice recipe!');
        $this->em->clear();

        $notification = $this->em->getRepository(Notification::class)->findOneBy(['recipient' => $author]);

        // Grab a valid 'notification-read' token from the recipient's own session
        // before switching to a user who has no business reading this notification.
        $this->client->loginUser($author);
        $crawler = $this->client->request('GET', '/recipes');
        $token = $this->extractCsrfToken(
            $crawler,
            'form[action="/notifications/' . $notification->getId() . '/read"] input[name="_token"]'
        );

        $intruder = $this->createUser('charlie');
        $this->client->loginUser($intruder);
        $this->client->request('POST', '/notifications/' . $notification->getId() . '/read', [
            '_token' => $token,
        ]);

        self::assertResponseStatusCodeSame(404);
    }

    public function testMarkNotificationAsReadWithInvalidCsrfTokenIsForbidden(): void
    {
        $author = $this->createUser('amelia');
        $recipe = $this->createRecipe($author, ['isPublic' => true]);

        $commenter = $this->createUser('ben');
        static::getContainer()->get(CommentService::class)->add($commenter, $recipe, 'Nice recipe!');
        $this->em->clear();

        $notification = $this->em->getRepository(Notification::class)->findOneBy(['recipient' => $author]);

        $this->client->loginUser($author);
        $this->client->request('POST', '/notifications/' . $notification->getId() . '/read', [
            '_token' => 'invalid-token',
        ]);

        self::assertResponseStatusCodeSame(403);
    }
}
