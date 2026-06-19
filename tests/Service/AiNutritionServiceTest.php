<?php

namespace App\Tests\Service;

use App\Entity\Ingredient;
use App\Entity\Recipe;
use App\Entity\RecipeIngredient;
use App\Entity\RecipeStep;
use App\Enum\Unit;
use App\Service\AiNutritionService;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class AiNutritionServiceTest extends TestCase
{
    public function testEstimateParsesPlainJsonResponse(): void
    {
        $service = $this->serviceReturning('{"total_calories": 1200, "confidence": "high"}');

        $result = $service->estimate($this->recipe());

        self::assertSame(1200, $result['total_calories']);
        self::assertSame('high', $result['confidence']);
    }

    public function testEstimateStripsMarkdownCodeFenceBeforeParsing(): void
    {
        $service = $this->serviceReturning("```json\n{\"total_calories\": 800}\n```");

        $result = $service->estimate($this->recipe());

        self::assertSame(800, $result['total_calories']);
    }

    public function testEstimateThrowsOnInvalidJsonResponse(): void
    {
        $service = $this->serviceReturning('not json at all');

        $this->expectException(\JsonException::class);

        $service->estimate($this->recipe());
    }

    public function testEstimateSendsRecipeDataAndApiKeyInRequest(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $response = $this->createStub(ResponseInterface::class);
        $response->method('toArray')->willReturn(['content' => [['text' => '{"total_calories": 1}']]]);

        $httpClient->expects($this->once())->method('request')
            ->with(
                'POST',
                'https://api.anthropic.com/v1/messages',
                $this->callback(function (array $options) {
                    self::assertSame('secret-key', $options['headers']['x-api-key']);

                    $prompt = $options['json']['messages'][0]['content'];
                    self::assertStringContainsString('Tomato Soup', $prompt);
                    self::assertStringContainsString('Tomato', $prompt);
                    self::assertStringContainsString('Simmer everything.', $prompt);

                    return true;
                })
            )
            ->willReturn($response);

        $service = new AiNutritionService($httpClient, 'secret-key');
        $service->estimate($this->recipe());
    }

    private function serviceReturning(string $responseText): AiNutritionService
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('toArray')->willReturn(['content' => [['text' => $responseText]]]);

        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('request')->willReturn($response);

        return new AiNutritionService($httpClient, 'secret-key');
    }

    private function recipe(): Recipe
    {
        $recipe = new Recipe();
        $recipe->setTitle('Tomato Soup')->setNotes('Comfort food')->setServings(4);
        $recipe->addRecipeIngredient(
            new RecipeIngredient()->setIngredient(new Ingredient()->setName('Tomato'))->setAmount(500)->setUnit(Unit::Gram)->setPosition(0)
        );
        $recipe->addStep(new RecipeStep()->setInstruction('Simmer everything.')->setPosition(0));

        return $recipe;
    }
}
