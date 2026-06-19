<?php

namespace App\Tests\Controller\Api;

use App\Entity\RecipeTag;
use App\Tests\Support\IntegrationTestCase;

class RecipeTagControllerTest extends IntegrationTestCase
{
    public function testIndexIsAnonymouslyAccessibleAndReturnsAllTags(): void
    {
        $this->em->persist(new RecipeTag()->setName('Vegan'));
        $this->em->persist(new RecipeTag()->setName('Dessert'));
        $this->em->flush();

        $this->client->request('GET', '/api/recipe-tags');

        self::assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertCount(2, $data);
    }

    public function testIndexFiltersBySearchQuery(): void
    {
        $this->em->persist(new RecipeTag()->setName('Vegan'));
        $this->em->persist(new RecipeTag()->setName('Dessert'));
        $this->em->flush();

        $this->client->request('GET', '/api/recipe-tags?search=Veg');

        self::assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertCount(1, $data);
        self::assertSame('Vegan', $data[0]['name']);
    }
}
