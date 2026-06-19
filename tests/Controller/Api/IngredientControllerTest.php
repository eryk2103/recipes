<?php

namespace App\Tests\Controller\Api;

use App\Entity\Ingredient;
use App\Tests\Support\IntegrationTestCase;

class IngredientControllerTest extends IntegrationTestCase
{
    public function testIndexIsAnonymouslyAccessibleAndReturnsAllIngredients(): void
    {
        $this->em->persist(new Ingredient()->setName('Flour'));
        $this->em->persist(new Ingredient()->setName('Sugar'));
        $this->em->flush();

        $this->client->request('GET', '/api/ingredients');

        self::assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertCount(2, $data);
    }

    public function testIndexFiltersBySearchQuery(): void
    {
        $this->em->persist(new Ingredient()->setName('Flour'));
        $this->em->persist(new Ingredient()->setName('Sugar'));
        $this->em->flush();

        $this->client->request('GET', '/api/ingredients?search=Flo');

        self::assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        self::assertCount(1, $data);
        self::assertSame('Flour', $data[0]['name']);
    }
}
