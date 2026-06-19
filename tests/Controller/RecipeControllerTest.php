<?php

namespace App\Tests\Controller;

use App\Dto\CreateRecipeDto;
use App\Dto\CreateRecipeIngredientDto;
use App\Dto\CreateRecipeStepDto;
use App\Entity\Notification;
use App\Entity\Recipe;
use App\Entity\RecipeComment;
use App\Enum\Unit;
use App\Service\CommentService;
use App\Service\RecipeService;
use App\Tests\Support\IntegrationTestCase;

class RecipeControllerTest extends IntegrationTestCase
{
    public function testDiscoverShowsOnlyPublicRecipes(): void
    {
        $author = $this->createUser('amelia');
        $this->createRecipe($author, ['title' => 'Public Pasta', 'isPublic' => true]);
        $this->createRecipe($author, ['title' => 'Secret Soup', 'isPublic' => false]);

        $this->client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Public Pasta');
        self::assertSelectorTextNotContains('body', 'Secret Soup');
    }

    public function testIndexRequiresLogin(): void
    {
        $this->client->request('GET', '/recipes');

        self::assertResponseRedirects('/login');
    }

    public function testIndexShowsOwnAndSavedRecipes(): void
    {
        $user = $this->createUser('amelia');
        $other = $this->createUser('ben');
        $own = $this->createRecipe($user, ['title' => 'My Own Dish']);
        $saved = $this->createRecipe($other, ['title' => 'Saved Dish', 'isPublic' => true]);

        $recipeService = static::getContainer()->get(RecipeService::class);
        $recipeService->save($user, $saved);

        $this->client->loginUser($user);
        $this->client->request('GET', '/recipes');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'My Own Dish');
        self::assertSelectorTextContains('body', 'Saved Dish');
    }

    public function testNewRecipeRequiresLogin(): void
    {
        $this->client->request('GET', '/recipes/new');

        self::assertResponseRedirects('/login');
    }

    public function testCreateRecipeWithValidDataPersistsAndRedirects(): void
    {
        $user = $this->createUser('amelia');
        $this->client->loginUser($user);

        $this->client->request('GET', '/recipes/new');
        $this->client->submitForm('Save recipe', [
            'recipe[title]' => 'Tomato Soup',
            'recipe[notes]' => 'Comfort food',
            'recipe[servings]' => 4,
            'recipe[cookTimeMinutes]' => 30,
            'recipe[isPublic]' => true,
            'recipe[recipeIngredients][0][position]' => 0,
            'recipe[recipeIngredients][0][amount]' => 500,
            'recipe[recipeIngredients][0][unit]' => 'g',
            'recipe[recipeIngredients][0][name]' => 'Tomato',
            'recipe[steps][0][position]' => 0,
            'recipe[steps][0][instruction]' => 'Simmer everything.',
        ]);

        self::assertResponseRedirects('/recipes');

        $recipe = $this->em->getRepository(Recipe::class)->findOneBy(['title' => 'Tomato Soup']);
        self::assertNotNull($recipe);
        self::assertTrue($recipe->isPublic());
        self::assertSame($user->getId(), $recipe->getAuthor()->getId());
        self::assertCount(1, $recipe->getRecipeIngredients());
        self::assertCount(1, $recipe->getSteps());
    }

    public function testCreateRecipeWithBlankTitleFailsValidation(): void
    {
        $user = $this->createUser('amelia');
        $this->client->loginUser($user);

        $this->client->request('GET', '/recipes/new');
        $this->client->submitForm('Save recipe', [
            'recipe[title]' => '',
            'recipe[notes]' => 'Comfort food',
            'recipe[servings]' => 4,
            'recipe[recipeIngredients][0][position]' => 0,
            'recipe[recipeIngredients][0][amount]' => 500,
            'recipe[recipeIngredients][0][unit]' => 'g',
            'recipe[recipeIngredients][0][name]' => 'Tomato',
            'recipe[steps][0][position]' => 0,
            'recipe[steps][0][instruction]' => 'Simmer everything.',
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('body', 'Please enter a recipe title.');
        self::assertNull($this->em->getRepository(Recipe::class)->findOneBy(['notes' => 'Comfort food']));
    }

    public function testShowRequiresLogin(): void
    {
        $author = $this->createUser('amelia');
        $recipe = $this->createRecipe($author, ['isPublic' => true]);

        $this->client->request('GET', '/recipes/' . $recipe->getId());

        self::assertResponseRedirects('/login');
    }

    public function testShowPublicRecipeIsVisibleToOtherUsers(): void
    {
        $author = $this->createUser('amelia');
        $recipe = $this->createRecipe($author, ['title' => 'Public Pasta', 'isPublic' => true]);

        $viewer = $this->createUser('ben');
        $this->client->loginUser($viewer);

        $this->client->request('GET', '/recipes/' . $recipe->getId());

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Public Pasta');
    }

    public function testShowPrivateRecipeReturns404ForOtherUsers(): void
    {
        $author = $this->createUser('amelia');
        $recipe = $this->createRecipe($author, ['isPublic' => false]);

        $viewer = $this->createUser('ben');
        $this->client->loginUser($viewer);

        $this->client->request('GET', '/recipes/' . $recipe->getId());

        self::assertResponseStatusCodeSame(404);
    }

    public function testShowPrivateRecipeIsVisibleToOwner(): void
    {
        $author = $this->createUser('amelia');
        $recipe = $this->createRecipe($author, ['title' => 'My Secret Soup', 'isPublic' => false]);

        $this->client->loginUser($author);
        $this->client->request('GET', '/recipes/' . $recipe->getId());

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'My Secret Soup');
    }

    public function testCommentOnAnotherUsersRecipeCreatesNotification(): void
    {
        $author = $this->createUser('amelia');
        $recipe = $this->createRecipe($author, ['isPublic' => true]);

        $commenter = $this->createUser('ben');
        $this->client->loginUser($commenter);

        $this->client->request('POST', '/recipes/' . $recipe->getId() . '/comment', [
            'body' => 'Looks delicious!',
        ]);

        self::assertResponseRedirects('/recipes/' . $recipe->getId());

        $this->em->clear();

        $comment = $this->em->getRepository(RecipeComment::class)->findOneBy(['recipe' => $recipe]);
        self::assertNotNull($comment);
        self::assertSame('Looks delicious!', $comment->getBody());

        $notification = $this->em->getRepository(Notification::class)->findOneBy(['recipient' => $author]);
        self::assertNotNull($notification);
        self::assertSame(Notification::TYPE_COMMENT, $notification->getType());
    }

    public function testCommentOnOwnRecipeDoesNotCreateNotification(): void
    {
        $author = $this->createUser('amelia');
        $recipe = $this->createRecipe($author, ['isPublic' => true]);

        $this->client->loginUser($author);
        $this->client->request('POST', '/recipes/' . $recipe->getId() . '/comment', [
            'body' => 'Note to self.',
        ]);

        self::assertResponseRedirects();

        $this->em->clear();
        self::assertCount(0, $this->em->getRepository(Notification::class)->findAll());
    }

    public function testCommentOnPrivateRecipeOfAnotherUserReturns404(): void
    {
        $author = $this->createUser('amelia');
        $recipe = $this->createRecipe($author, ['isPublic' => false]);

        $other = $this->createUser('ben');
        $this->client->loginUser($other);

        $this->client->request('POST', '/recipes/' . $recipe->getId() . '/comment', [
            'body' => 'Sneaky comment',
        ]);

        self::assertResponseStatusCodeSame(404);
    }

    public function testDeleteOwnCommentSoftDeletesIt(): void
    {
        $author = $this->createUser('amelia');
        $recipe = $this->createRecipe($author, ['isPublic' => true]);

        $commentService = static::getContainer()->get(CommentService::class);
        $commentService->add($author, $recipe, 'My own comment');
        $this->em->clear();

        $recipe = $this->em->getRepository(Recipe::class)->find($recipe->getId());
        $comment = $this->em->getRepository(RecipeComment::class)->findOneBy(['recipe' => $recipe]);

        $this->client->loginUser($author);
        $this->client->request('GET', '/recipes/' . $recipe->getId());
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/recipes/' . $recipe->getId());

        $this->em->clear();
        $comment = $this->em->getRepository(RecipeComment::class)->find($comment->getId());
        self::assertTrue($comment->isDeleted());
    }

    public function testDeleteCommentWithInvalidCsrfTokenIsForbidden(): void
    {
        $author = $this->createUser('amelia');
        $recipe = $this->createRecipe($author, ['isPublic' => true]);

        $commentService = static::getContainer()->get(CommentService::class);
        $commentService->add($author, $recipe, 'My own comment');
        $this->em->clear();

        $this->client->loginUser($author);
        $this->client->request('GET', '/recipes/' . $recipe->getId());
        $this->client->submitForm('Delete', ['_token' => 'invalid-token']);

        self::assertResponseStatusCodeSame(403);
    }

    public function testDeleteAnotherUsersCommentReturns404(): void
    {
        $author = $this->createUser('amelia');
        $recipe = $this->createRecipe($author, ['isPublic' => true]);

        $commentService = static::getContainer()->get(CommentService::class);
        $commentService->add($author, $recipe, 'My own comment');
        $this->em->clear();

        $comment = $this->em->getRepository(RecipeComment::class)->findOneBy(['recipe' => $recipe]);

        // Only the comment's author sees the delete form/token, so grab a valid token
        // from their session before switching to the other (non-owner) user.
        $this->client->loginUser($author);
        $crawler = $this->client->request('GET', '/recipes/' . $recipe->getId());
        $token = $this->extractCsrfToken(
            $crawler,
            'form[action="/recipes/' . $recipe->getId() . '/comment/' . $comment->getId() . '/delete"] input[name="_token"]'
        );

        $other = $this->createUser('ben');
        $this->client->loginUser($other);
        $this->client->request('POST', '/recipes/' . $recipe->getId() . '/comment/' . $comment->getId() . '/delete', [
            '_token' => $token,
        ]);

        self::assertResponseStatusCodeSame(404);
    }

    public function testEditRequiresOwnership(): void
    {
        $author = $this->createUser('amelia');
        $recipe = $this->createRecipe($author);

        $other = $this->createUser('ben');
        $this->client->loginUser($other);

        $this->client->request('GET', '/recipes/' . $recipe->getId() . '/edit');

        self::assertResponseStatusCodeSame(404);
    }

    public function testEditRecipeUpdatesIt(): void
    {
        $author = $this->createUser('amelia');

        $dto = new CreateRecipeDto();
        $dto->title = 'Old Title';
        $dto->notes = 'Old notes';
        $dto->servings = 4;
        $ingredientDto = new CreateRecipeIngredientDto();
        $ingredientDto->amount = 50;
        $ingredientDto->unit = Unit::Gram;
        $ingredientDto->name = 'Pepper';
        $ingredientDto->position = 0;
        $dto->recipeIngredients[] = $ingredientDto;
        $stepDto = new CreateRecipeStepDto();
        $stepDto->instruction = 'Old instruction.';
        $stepDto->position = 0;
        $dto->steps[] = $stepDto;

        $recipe = static::getContainer()->get(RecipeService::class)->create($dto, $author);
        $this->em->clear();
        $recipe = $this->em->getRepository(Recipe::class)->find($recipe->getId());

        $this->client->loginUser($author);
        $this->client->request('GET', '/recipes/' . $recipe->getId() . '/edit');
        $this->client->submitForm('Save changes', [
            'recipe[title]' => 'New Title',
            'recipe[notes]' => 'Updated notes',
            'recipe[servings]' => 2,
            'recipe[cookTimeMinutes]' => 10,
            'recipe[recipeIngredients][0][position]' => 0,
            'recipe[recipeIngredients][0][amount]' => 100,
            'recipe[recipeIngredients][0][unit]' => 'g',
            'recipe[recipeIngredients][0][name]' => 'Salt',
            'recipe[steps][0][position]' => 0,
            'recipe[steps][0][instruction]' => 'Mix well.',
        ]);

        self::assertResponseRedirects('/recipes');

        $this->em->clear();
        $recipe = $this->em->getRepository(Recipe::class)->find($recipe->getId());
        self::assertSame('New Title', $recipe->getTitle());
    }
}
