<?php

namespace App\Tests\Support;

use App\Entity\Recipe;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

abstract class IntegrationTestCase extends WebTestCase
{
    protected KernelBrowser $client;
    protected EntityManagerInterface $em;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);

        $this->em->getConnection()->executeStatement(
            'TRUNCATE TABLE notification, recipe_comment, recipe_saved, recipe_recipe_tag, '
            . 'recipe_ingredient, recipe_step, recipe, recipe_tag, ingredient, "user", messenger_messages '
            . 'RESTART IDENTITY CASCADE'
        );
    }

    protected function createUser(string $username = 'amelia', string $password = 'password123'): User
    {
        $user = new User();
        $user->setUsername($username);
        $user->setPassword(static::getContainer()->get(UserPasswordHasherInterface::class)->hashPassword($user, $password));

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    protected function createRecipe(User $author, array $attrs = []): Recipe
    {
        $recipe = new Recipe();
        $recipe->setAuthor($author);
        $recipe->setTitle($attrs['title'] ?? 'Test Recipe');
        $recipe->setNotes($attrs['notes'] ?? 'Some notes');
        $recipe->setServings($attrs['servings'] ?? 4);
        $recipe->setCookTimeMinutes($attrs['cookTimeMinutes'] ?? 20);
        $recipe->setIsPublic($attrs['isPublic'] ?? false);

        $this->em->persist($recipe);
        $this->em->flush();

        return $recipe;
    }

    protected function extractCsrfToken(Crawler $crawler, string $selector, string $attribute = 'value'): string
    {
        return $crawler->filter($selector)->attr($attribute);
    }
}
