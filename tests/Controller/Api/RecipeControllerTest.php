<?php

namespace App\Tests\Controller\Api;

use App\Entity\Notification;
use App\Entity\Recipe;
use App\Entity\RecipeSaved;
use App\Entity\User;
use App\Service\RecipeService;
use App\Tests\Support\IntegrationTestCase;

class RecipeControllerTest extends IntegrationTestCase
{
    public function testIndexRequiresAuthentication(): void
    {
        $this->client->request('GET', '/api/recipes');

        self::assertResponseRedirects('/login');
    }

    public function testSaveRequiresAuthentication(): void
    {
        $author = $this->createUser('amelia');
        $recipe = $this->createRecipe($author, ['isPublic' => true]);

        $this->client->request('POST', '/api/recipes/' . $recipe->getId() . '/save');

        self::assertResponseRedirects('/login');
    }

    public function testUnsaveRequiresAuthentication(): void
    {
        $author = $this->createUser('amelia');
        $recipe = $this->createRecipe($author, ['isPublic' => true]);

        $this->client->request('POST', '/api/recipes/' . $recipe->getId() . '/unsave');

        self::assertResponseRedirects('/login');
    }

    public function testPublicEndpointIsAnonymouslyAccessibleAndOnlyReturnsPublicRecipes(): void
    {
        $author = $this->createUser('amelia');
        $this->createRecipe($author, ['title' => 'Public Pasta', 'isPublic' => true]);
        $this->createRecipe($author, ['title' => 'Secret Soup', 'isPublic' => false]);

        $this->client->request('GET', '/api/recipes/public');

        self::assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertCount(1, $data);
        self::assertSame('Public Pasta', $data[0]['title']);
    }

    public function testPublicEndpointFiltersBySearchQuery(): void
    {
        $author = $this->createUser('amelia');
        $this->createRecipe($author, ['title' => 'Tomato Soup', 'isPublic' => true]);
        $this->createRecipe($author, ['title' => 'Pumpkin Pie', 'isPublic' => true]);

        $this->client->request('GET', '/api/recipes/public?search=tomato');

        self::assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertCount(1, $data);
        self::assertSame('Tomato Soup', $data[0]['title']);
    }

    public function testIndexReturnsOnlyAuthenticatedUsersOwnRecipes(): void
    {
        $user = $this->createUser('amelia');
        $other = $this->createUser('ben');
        $this->createRecipe($user, ['title' => 'My Dish']);
        $this->createRecipe($other, ['title' => 'Their Dish']);

        $this->client->loginUser($user);
        $this->client->request('GET', '/api/recipes');

        self::assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertCount(1, $data);
        self::assertSame('My Dish', $data[0]['title']);
    }

    public function testIndexFiltersBySearchQuery(): void
    {
        $user = $this->createUser('amelia');
        $this->createRecipe($user, ['title' => 'Tomato Soup']);
        $this->createRecipe($user, ['title' => 'Pumpkin Pie']);

        $this->client->loginUser($user);
        $this->client->request('GET', '/api/recipes?search=pumpkin');

        self::assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertCount(1, $data);
        self::assertSame('Pumpkin Pie', $data[0]['title']);
    }

    public function testSaveAnotherUsersPublicRecipeCreatesSaveAndNotification(): void
    {
        $author = $this->createUser('amelia');
        $recipe = $this->createRecipe($author, ['isPublic' => true]);

        $saver = $this->createUser('ben');
        $this->client->loginUser($saver);

        $crawler = $this->client->request('GET', '/recipes/' . $recipe->getId());
        $token = $this->extractCsrfToken($crawler, '[data-csrf-token]', 'data-csrf-token');

        $this->client->request('POST', '/api/recipes/' . $recipe->getId() . '/save', [], [], [
            'HTTP_X_CSRF_TOKEN' => $token,
        ]);

        self::assertResponseStatusCodeSame(204);

        $this->em->clear();
        self::assertNotNull($this->em->getRepository(RecipeSaved::class)->findOneBy(['recipe' => $recipe, 'acount' => $saver]));

        $notification = $this->em->getRepository(Notification::class)->findOneBy(['recipient' => $author]);
        self::assertNotNull($notification);
        self::assertSame(Notification::TYPE_SAVE, $notification->getType());
    }

    public function testSaveWithInvalidCsrfTokenIsForbidden(): void
    {
        $author = $this->createUser('amelia');
        $recipe = $this->createRecipe($author, ['isPublic' => true]);

        $saver = $this->createUser('ben');
        $this->client->loginUser($saver);

        $this->client->request('POST', '/api/recipes/' . $recipe->getId() . '/save', [], [], [
            'HTTP_X_CSRF_TOKEN' => 'invalid-token',
        ]);

        self::assertResponseStatusCodeSame(403);
    }

    public function testSaveOwnRecipeReturnsBadRequest(): void
    {
        $author = $this->createUser('amelia');
        $recipe = $this->createRecipe($author, ['isPublic' => true]);

        // The save/unsave button (and its CSRF token) only renders on recipes the
        // viewer doesn't own, so grab a valid 'recipe-save' token from someone else's
        // public recipe instead — the token isn't scoped to a specific recipe id.
        $decoyAuthor = $this->createUser('ben');
        $decoyRecipe = $this->createRecipe($decoyAuthor, ['isPublic' => true]);

        $this->client->loginUser($author);
        $crawler = $this->client->request('GET', '/recipes/' . $decoyRecipe->getId());
        $token = $this->extractCsrfToken($crawler, '[data-csrf-token]', 'data-csrf-token');

        $this->client->request('POST', '/api/recipes/' . $recipe->getId() . '/save', [], [], [
            'HTTP_X_CSRF_TOKEN' => $token,
        ]);

        self::assertResponseStatusCodeSame(400);
    }

    public function testUnsaveRemovesExistingSave(): void
    {
        $author = $this->createUser('amelia');
        $recipe = $this->createRecipe($author, ['isPublic' => true]);

        $saver = $this->createUser('ben');
        static::getContainer()->get(RecipeService::class)->save($saver, $recipe);
        $this->em->clear();
        $recipe = $this->em->getRepository(Recipe::class)->find($recipe->getId());
        $saver = $this->em->getRepository(User::class)->find($saver->getId());

        $this->client->loginUser($saver);
        $crawler = $this->client->request('GET', '/recipes/' . $recipe->getId());
        $token = $this->extractCsrfToken($crawler, '[data-csrf-token]', 'data-csrf-token');

        $this->client->request('POST', '/api/recipes/' . $recipe->getId() . '/unsave', [], [], [
            'HTTP_X_CSRF_TOKEN' => $token,
        ]);

        self::assertResponseStatusCodeSame(204);

        $this->em->clear();
        self::assertNull($this->em->getRepository(RecipeSaved::class)->findOneBy(['recipe' => $recipe, 'acount' => $saver]));
    }

    public function testUnsaveRecipeThatWasNeverSavedReturnsBadRequest(): void
    {
        $author = $this->createUser('amelia');
        $recipe = $this->createRecipe($author, ['isPublic' => true]);

        $other = $this->createUser('ben');
        $this->client->loginUser($other);
        $crawler = $this->client->request('GET', '/recipes/' . $recipe->getId());
        $token = $this->extractCsrfToken($crawler, '[data-csrf-token]', 'data-csrf-token');

        $this->client->request('POST', '/api/recipes/' . $recipe->getId() . '/unsave', [], [], [
            'HTTP_X_CSRF_TOKEN' => $token,
        ]);

        self::assertResponseStatusCodeSame(400);
    }
}
