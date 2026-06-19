<?php

namespace App\Tests\Service;

use App\Dto\CreateRecipeDto;
use App\Dto\CreateRecipeIngredientDto;
use App\Dto\CreateRecipeStepDto;
use App\Entity\Ingredient;
use App\Entity\Notification;
use App\Entity\Recipe;
use App\Entity\RecipeIngredient;
use App\Entity\RecipeSaved;
use App\Entity\RecipeStep;
use App\Entity\RecipeTag;
use App\Entity\User;
use App\Enum\NutritionStatus;
use App\Enum\Unit;
use App\Message\CalculateRecipeNutritionMessage;
use App\Repository\IngredientRepository;
use App\Repository\RecipeRepository;
use App\Repository\RecipeSavedRepository;
use App\Repository\RecipeTagRepository;
use App\Service\NotificationService;
use App\Service\RecipeService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

#[AllowMockObjectsWithoutExpectations]
class RecipeServiceTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private RecipeRepository $recipeRepository;
    private IngredientRepository $ingredientRepository;
    private RecipeTagRepository $recipeTagRepository;
    private RecipeSavedRepository $recipeSavedRepository;
    private NotificationService $notificationService;
    private MessageBusInterface $bus;
    private RecipeService $service;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->recipeRepository = $this->createMock(RecipeRepository::class);
        $this->ingredientRepository = $this->createMock(IngredientRepository::class);
        $this->recipeTagRepository = $this->createMock(RecipeTagRepository::class);
        $this->recipeSavedRepository = $this->createMock(RecipeSavedRepository::class);
        $this->notificationService = $this->createMock(NotificationService::class);
        $this->bus = $this->createMock(MessageBusInterface::class);
        $this->bus->method('dispatch')->willReturn(new Envelope(new \stdClass()));

        // Persisting a new Recipe is normally how Doctrine assigns its id; simulate
        // that here since create()/update() dispatch a message keyed by recipe id.
        $this->entityManager->method('persist')->willReturnCallback(function ($entity) {
            if ($entity instanceof Recipe && $entity->getId() === null) {
                (new \ReflectionProperty(Recipe::class, 'id'))->setValue($entity, 1);
            }
        });

        $this->service = new RecipeService(
            $this->entityManager,
            $this->recipeRepository,
            $this->ingredientRepository,
            $this->recipeTagRepository,
            $this->recipeSavedRepository,
            $this->notificationService,
            $this->bus,
        );
    }

    public function testIsSavedReturnsFalseWithoutQueryingWhenUserIsNull(): void
    {
        $this->recipeSavedRepository->expects($this->never())->method('findOneBy');

        self::assertFalse($this->service->isSaved(null, new Recipe()));
    }

    public function testIsSavedReturnsTrueWhenSavedRowExists(): void
    {
        $recipe = new Recipe();
        $user = new User();

        $this->recipeSavedRepository->expects($this->once())->method('findOneBy')
            ->with(['recipe' => $recipe, 'acount' => $user])
            ->willReturn(new RecipeSaved());

        self::assertTrue($this->service->isSaved($user, $recipe));
    }

    public function testIsSavedReturnsFalseWhenNoSavedRowExists(): void
    {
        $this->recipeSavedRepository->method('findOneBy')->willReturn(null);

        self::assertFalse($this->service->isSaved(new User(), new Recipe()));
    }

    public function testSaveThrowsWhenRecipeAlreadySaved(): void
    {
        $this->recipeSavedRepository->method('findOneBy')->willReturn(new RecipeSaved());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Recipe already saved');

        $this->service->save(new User(), $this->recipeWithAuthor(new User()));
    }

    public function testSaveThrowsWhenSavingOwnRecipe(): void
    {
        $this->recipeSavedRepository->method('findOneBy')->willReturn(null);

        $author = $this->userWithId(1);
        $recipe = $this->recipeWithAuthor($author);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot save your own recipe.');

        $this->service->save($author, $recipe);
    }

    public function testSaveCreatesSavedRowAndNotifiesAuthor(): void
    {
        $this->recipeSavedRepository->method('findOneBy')->willReturn(null);

        $author = $this->userWithId(1);
        $saver = $this->userWithId(2);
        $recipe = $this->recipeWithAuthor($author);

        $this->entityManager->expects($this->once())->method('persist')->with($this->isInstanceOf(RecipeSaved::class));
        $this->notificationService->expects($this->once())->method('notify')
            ->with($author, $saver, $recipe, Notification::TYPE_SAVE);
        $this->entityManager->expects($this->once())->method('flush');

        $this->service->save($saver, $recipe);
    }

    public function testUnsaveThrowsWhenNotPreviouslySaved(): void
    {
        $this->recipeSavedRepository->method('findOneBy')->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Recipe save not found');

        $this->service->unsave(new User(), new Recipe());
    }

    public function testUnsaveRemovesExistingSavedRow(): void
    {
        $savedRow = new RecipeSaved();
        $this->recipeSavedRepository->method('findOneBy')->willReturn($savedRow);

        $this->entityManager->expects($this->once())->method('remove')->with($savedRow);
        $this->entityManager->expects($this->once())->method('flush');

        $this->service->unsave(new User(), new Recipe());
    }

    public function testCreateReusesExistingIngredientsAndTagsByName(): void
    {
        $existingIngredient = new Ingredient()->setName('Flour');
        $existingTag = new RecipeTag()->setName('Vegan');

        $this->ingredientRepository->method('findByNames')->willReturn([$existingIngredient]);
        $this->recipeTagRepository->method('findByNames')->willReturn([$existingTag]);

        $dto = $this->buildDto(
            ingredients: [['name' => 'Flour', 'amount' => 200, 'unit' => Unit::Gram, 'position' => 0]],
            tags: ['Vegan'],
        );

        $recipe = $this->service->create($dto, new User());

        self::assertCount(1, $recipe->getRecipeIngredients());
        $recipeIngredient = $recipe->getRecipeIngredients()->first();
        self::assertSame($existingIngredient, $recipeIngredient->getIngredient());
        self::assertSame(200, $recipeIngredient->getAmount());
        self::assertSame(Unit::Gram, $recipeIngredient->getUnit());

        self::assertCount(1, $recipe->getTags());
        self::assertSame($existingTag, $recipe->getTags()->first());
    }

    public function testCreateBuildsNewIngredientsAndTagsWhenNoneMatchByName(): void
    {
        $this->ingredientRepository->method('findByNames')->willReturn([]);
        $this->recipeTagRepository->method('findByNames')->willReturn([]);

        $dto = $this->buildDto(
            ingredients: [['name' => 'Sugar', 'amount' => 50, 'unit' => Unit::Gram, 'position' => 0]],
            tags: ['Dessert'],
        );

        $recipe = $this->service->create($dto, new User());

        $recipeIngredient = $recipe->getRecipeIngredients()->first();
        self::assertSame('Sugar', $recipeIngredient->getIngredient()->getName());

        $tag = $recipe->getTags()->first();
        self::assertSame('Dessert', $tag->getName());
    }

    public function testCreateTrimsTagNamesAndSkipsBlankOnes(): void
    {
        $this->ingredientRepository->method('findByNames')->willReturn([]);
        $this->recipeTagRepository->method('findByNames')->willReturn([]);

        $dto = $this->buildDto(tags: ['  Vegan  ', '', '   ']);

        $recipe = $this->service->create($dto, new User());

        self::assertCount(1, $recipe->getTags());
        self::assertSame('Vegan', $recipe->getTags()->first()->getName());
    }

    public function testCreateSetsNutritionPendingAndDispatchesMessageWithRecipeId(): void
    {
        $this->ingredientRepository->method('findByNames')->willReturn([]);
        $this->recipeTagRepository->method('findByNames')->willReturn([]);

        $dispatched = null;
        $this->bus->expects($this->once())->method('dispatch')
            ->with($this->callback(function (CalculateRecipeNutritionMessage $message) use (&$dispatched) {
                $dispatched = $message;

                return true;
            }))
            ->willReturn(new Envelope(new \stdClass()));

        $recipe = $this->service->create($this->buildDto(), new User());

        self::assertSame(NutritionStatus::PENDING, $recipe->getNutritionStatus());
        self::assertSame(1, $dispatched->recipeId);
    }

    public function testUpdateReplacesExistingIngredientsStepsAndTags(): void
    {
        $recipe = new Recipe();
        $recipe->setTitle('Old')->setNotes('Old notes')->setServings(1)->setIsPublic(false);
        $recipe->addRecipeIngredient(
            new RecipeIngredient()->setIngredient(new Ingredient()->setName('Old Ingredient'))->setAmount(1)->setUnit(Unit::Gram)->setPosition(0)
        );
        $recipe->addStep(new RecipeStep()->setInstruction('Old step')->setPosition(0));
        $recipe->addTag(new RecipeTag()->setName('Old Tag'));
        (new \ReflectionProperty(Recipe::class, 'id'))->setValue($recipe, 7);

        $this->ingredientRepository->method('findByNames')->willReturn([]);
        $this->recipeTagRepository->method('findByNames')->willReturn([]);

        $dto = $this->buildDto(
            title: 'New',
            ingredients: [['name' => 'New Ingredient', 'amount' => 10, 'unit' => Unit::Kilogram, 'position' => 0]],
            steps: ['New step'],
            tags: ['New Tag'],
        );

        $updated = $this->service->update($recipe, $dto);

        self::assertSame('New', $updated->getTitle());
        self::assertCount(1, $updated->getRecipeIngredients());
        self::assertSame('New Ingredient', $updated->getRecipeIngredients()->first()->getIngredient()->getName());
        self::assertCount(1, $updated->getSteps());
        self::assertSame('New step', $updated->getSteps()->first()->getInstruction());
        self::assertCount(1, $updated->getTags());
        self::assertSame('New Tag', $updated->getTags()->first()->getName());
        self::assertSame(NutritionStatus::PENDING, $updated->getNutritionStatus());
    }

    public function testToDtoMapsRecipeAndNestedCollectionsToDto(): void
    {
        $recipe = new Recipe();
        $recipe->setTitle('Tomato Soup')
            ->setNotes('Comfort food')
            ->setServings(4)
            ->setCookTimeMinutes(30)
            ->setIsPublic(true)
            ->setPhoto('photo.jpg');

        $recipe->addRecipeIngredient(
            new RecipeIngredient()->setIngredient(new Ingredient()->setName('Tomato'))->setAmount(500)->setUnit(Unit::Gram)->setPosition(0)
        );
        $recipe->addStep(new RecipeStep()->setInstruction('Simmer everything.')->setPosition(0));
        $recipe->addTag(new RecipeTag()->setName('Vegan'));

        $dto = $this->service->toDto($recipe);

        self::assertSame('Tomato Soup', $dto->title);
        self::assertSame('Comfort food', $dto->notes);
        self::assertSame(4, $dto->servings);
        self::assertSame(30, $dto->cookTimeMinutes);
        self::assertTrue($dto->isPublic);
        self::assertSame('photo.jpg', $dto->photo);

        self::assertCount(1, $dto->recipeIngredients);
        self::assertSame('Tomato', $dto->recipeIngredients[0]->name);
        self::assertSame(500, $dto->recipeIngredients[0]->amount);
        self::assertSame(Unit::Gram, $dto->recipeIngredients[0]->unit);

        self::assertCount(1, $dto->steps);
        self::assertSame('Simmer everything.', $dto->steps[0]->instruction);

        self::assertSame(['Vegan'], $dto->tags);
    }

    private function userWithId(int $id): User
    {
        $user = new User();
        (new \ReflectionProperty(User::class, 'id'))->setValue($user, $id);

        return $user;
    }

    private function recipeWithAuthor(User $author): Recipe
    {
        $recipe = new Recipe();
        $recipe->setAuthor($author);

        return $recipe;
    }

    /**
     * @param array{name: string, amount: int, unit: Unit, position: int}[] $ingredients
     * @param string[] $steps
     * @param string[] $tags
     */
    private function buildDto(
        string $title = 'Recipe',
        array $ingredients = [],
        array $steps = [],
        array $tags = [],
    ): CreateRecipeDto {
        $dto = new CreateRecipeDto();
        $dto->title = $title;
        $dto->notes = 'Notes';
        $dto->servings = 4;

        foreach ($ingredients as $ingredient) {
            $ingredientDto = new CreateRecipeIngredientDto();
            $ingredientDto->name = $ingredient['name'];
            $ingredientDto->amount = $ingredient['amount'];
            $ingredientDto->unit = $ingredient['unit'];
            $ingredientDto->position = $ingredient['position'];
            $dto->recipeIngredients[] = $ingredientDto;
        }

        foreach ($steps as $position => $instruction) {
            $stepDto = new CreateRecipeStepDto();
            $stepDto->instruction = $instruction;
            $stepDto->position = $position;
            $dto->steps[] = $stepDto;
        }

        $dto->tags = $tags;

        return $dto;
    }
}
