<?php

namespace App\Service;

use App\Entity\Recipe;
use App\Service\Interface\NutritionServiceInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AiNutritionService implements NutritionServiceInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $aiApiKey,
    ) {}

    function estimate(Recipe $recipe): array
    {
        $title = $recipe->getTitle();
        $notes = $recipe->getNotes();
        $servings = $recipe->getServings();

        $recipeIngredients = $recipe->getRecipeIngredients();
        $ingredients = [];
        foreach ($recipeIngredients as $recipeIngredient) {
            $ingredients[] = [
                'name' => $recipeIngredient->getIngredient()->getName(),
                'amount' => $recipeIngredient->getAmount(),
                'unit' => $recipeIngredient->getUnit(),
            ];
        }

        $recipeSteps= $recipe->getSteps();
        $steps = [];
        foreach ($recipeSteps as $recipeStep) {
            $steps[] = $recipeStep->getInstruction();
        }

        $data = json_encode([
            'title' => $title,
            'notes' => $notes,
            'servings' => $servings,
            'ingredients' => $ingredients,
            'steps' => $steps,
        ]);

        $prompt = <<<PROMPT
        You are a nutrition expert. Estimate the calorie content for this recipe.

        {$data}
        Respond ONLY with a JSON object, no markdown, no explanation:
        {
          "total_calories": 1200,
          "calories_per_serving": 300,
          "breakdown": [
            {"ingredient": "chicken breast", "calories": 550},
            {"ingredient": "olive oil", "calories": 240}
          ],
          "confidence": "high|medium|low",
        }
        PROMPT;

        $response = $this->httpClient->request("POST", "https://api.anthropic.com/v1/messages", [
            'headers' => [
                'x-api-key'         => $this->aiApiKey,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ],
            'json' => [
                'model'      => 'claude-haiku-4-5-20251001',
                'max_tokens' => 1024,
                'messages'   => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ],
        ]);

        $data = $response->toArray();
        $text = $data['content'][0]['text'];

        $clean = preg_replace('/^```json\s*|\s*```$/s', '', trim($text));

        return json_decode($clean, true, flags: JSON_THROW_ON_ERROR);
    }
}
