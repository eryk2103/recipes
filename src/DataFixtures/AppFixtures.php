<?php

namespace App\DataFixtures;

use App\Entity\Ingredient;
use App\Entity\Notification;
use App\Entity\Recipe;
use App\Entity\RecipeComment;
use App\Entity\RecipeIngredient;
use App\Entity\RecipeSaved;
use App\Entity\RecipeStep;
use App\Entity\RecipeTag;
use App\Entity\User;
use App\Enum\NutritionStatus;
use App\Enum\Unit;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $users = $this->loadUsers($manager);
        $tags = $this->loadTags($manager);
        $ingredients = $this->loadIngredients($manager);
        $recipes = $this->loadRecipes($manager, $users, $tags, $ingredients);
        $this->loadCommentsAndSaves($manager, $users, $recipes);

        $manager->flush();
    }

    /** @return array<string, User> */
    private function loadUsers(ObjectManager $manager): array
    {
        $definitions = [
            'amelia' => 'Amelia Clarke',
            'marco' => 'Marco Rossi',
            'yuki' => 'Yuki Tanaka',
            'devon' => 'Devon Lee',
        ];

        $users = [];
        foreach ($definitions as $username => $displayName) {
            $user = new User();
            $user->setUsername($username);
            $user->setRoles(['ROLE_USER']);
            $user->setPassword($this->passwordHasher->hashPassword($user, 'password123'));

            $manager->persist($user);
            $users[$username] = $user;
        }

        return $users;
    }

    /** @return array<string, RecipeTag> */
    private function loadTags(ObjectManager $manager): array
    {
        $names = [
            'Italian', 'Thai', 'French', 'Japanese',
            'Vegetarian', 'Spicy', 'Breakfast', 'Dinner',
            'Quick', 'Comfort Food', 'Sweet',
            'Indian', 'Mexican', 'Salad', 'Soup', 'Dessert', 'Vegan',
        ];

        $tags = [];
        foreach ($names as $name) {
            $tag = new RecipeTag();
            $tag->setName($name);

            $manager->persist($tag);
            $tags[$name] = $tag;
        }

        return $tags;
    }

    /** @return array<string, Ingredient> */
    private function loadIngredients(ObjectManager $manager): array
    {
        $names = [
            'Pizza dough', 'Tomato sauce', 'Mozzarella', 'Fresh basil',
            'Chicken breast', 'Thai basil', 'Bird\'s eye chili', 'Fish sauce',
            'Arborio rice', 'Mushrooms', 'Parmesan', 'White wine',
            'Avocado', 'Egg', 'Sourdough bread', 'Lemon juice',
            'Beef chuck', 'Carrot', 'Red wine', 'Pearl onion',
            'Flour', 'Matcha powder', 'Milk', 'Maple syrup',
            'Chicken thigh', 'Yogurt', 'Garam masala', 'Tomato puree',
            'Cucumber', 'Feta', 'Olives', 'Red onion',
            'Ground beef', 'Taco shells', 'Cheddar', 'Lettuce',
            'Canned tomatoes', 'Onion', 'Vegetable stock', 'Cream',
            'Rice noodles', 'Bean sprouts', 'Peanuts', 'Tamarind paste',
            'Butter', 'Brown sugar', 'Chocolate chips', 'Vanilla extract',
            'Yellow onion', 'Beef stock', 'Gruyere', 'Baguette',
            'Quinoa', 'Chickpeas', 'Kale', 'Tahini',
            'Bell pepper', 'Paprika', 'Romaine lettuce', 'Croutons', 'Caesar dressing',
        ];

        $ingredients = [];
        foreach ($names as $name) {
            $ingredient = new Ingredient();
            $ingredient->setName($name);

            $manager->persist($ingredient);
            $ingredients[$name] = $ingredient;
        }

        return $ingredients;
    }

    /**
     * @param array<string, User> $users
     * @param array<string, RecipeTag> $tags
     * @param array<string, Ingredient> $ingredients
     * @return array<string, Recipe>
     */
    private function loadRecipes(ObjectManager $manager, array $users, array $tags, array $ingredients): array
    {
        $definitions = [
            [
                'title' => 'Classic Margherita Pizza',
                'notes' => 'A simple, traditional Neapolitan pizza that lets the quality of the ingredients shine.',
                'author' => 'amelia',
                'servings' => 4,
                'cookTimeMinutes' => 25,
                'isPublic' => true,
                'tags' => ['Italian', 'Vegetarian', 'Dinner'],
                'ingredients' => [
                    ['Pizza dough', 1, Unit::Piece],
                    ['Tomato sauce', 150, Unit::Gram],
                    ['Mozzarella', 200, Unit::Gram],
                    ['Fresh basil', 10, Unit::Gram],
                ],
                'steps' => [
                    'Preheat the oven to its highest setting with a pizza stone inside.',
                    'Stretch the dough out into a round on a floured surface.',
                    'Spread the tomato sauce evenly, leaving a border for the crust.',
                    'Tear the mozzarella over the top and bake until bubbling and golden.',
                    'Finish with fresh basil leaves before serving.',
                ],
                'nutritionStatus' => NutritionStatus::DONE,
                'nutrition' => [
                    'total_calories' => 1280,
                    'calories_per_serving' => 320,
                    'breakdown' => [
                        ['ingredient' => 'Pizza dough', 'calories' => 600],
                        ['ingredient' => 'Mozzarella', 'calories' => 540],
                        ['ingredient' => 'Tomato sauce', 'calories' => 90],
                        ['ingredient' => 'Fresh basil', 'calories' => 5],
                    ],
                    'confidence' => 'high',
                ],
            ],
            [
                'title' => 'Spicy Thai Basil Chicken',
                'notes' => 'A fast weeknight stir-fry with a serious chili kick. Adjust the chilies to taste.',
                'author' => 'marco',
                'servings' => 2,
                'cookTimeMinutes' => 15,
                'isPublic' => true,
                'tags' => ['Thai', 'Spicy', 'Dinner', 'Quick'],
                'ingredients' => [
                    ['Chicken breast', 300, Unit::Gram],
                    ['Thai basil', 20, Unit::Gram],
                    ['Bird\'s eye chili', 3, Unit::Piece],
                    ['Fish sauce', 2, Unit::Tablespoon],
                ],
                'steps' => [
                    'Dice the chicken breast into small, even pieces.',
                    'Crush the chilies and fry briefly in hot oil.',
                    'Add the chicken and cook through over high heat.',
                    'Stir in the fish sauce and Thai basil, then serve immediately.',
                ],
                'nutritionStatus' => NutritionStatus::DONE,
                'nutrition' => [
                    'total_calories' => 620,
                    'calories_per_serving' => 310,
                    'breakdown' => [
                        ['ingredient' => 'Chicken breast', 'calories' => 495],
                        ['ingredient' => 'Fish sauce', 'calories' => 20],
                        ['ingredient' => 'Thai basil', 'calories' => 5],
                        ['ingredient' => 'Bird\'s eye chili', 'calories' => 10],
                    ],
                    'confidence' => 'medium',
                ],
            ],
            [
                'title' => 'Creamy Mushroom Risotto',
                'notes' => 'Slow, stirred risotto with a deep mushroom flavour. Worth the patience.',
                'author' => 'yuki',
                'servings' => 4,
                'cookTimeMinutes' => 40,
                'isPublic' => true,
                'tags' => ['Italian', 'Vegetarian', 'Comfort Food'],
                'ingredients' => [
                    ['Arborio rice', 320, Unit::Gram],
                    ['Mushrooms', 250, Unit::Gram],
                    ['Parmesan', 80, Unit::Gram],
                    ['White wine', 100, Unit::Milliliter],
                ],
                'steps' => [
                    'Sauté the sliced mushrooms until golden, then set aside.',
                    'Toast the rice in the same pan for a minute.',
                    'Deglaze with white wine and let it reduce.',
                    'Add stock a ladle at a time, stirring constantly, until creamy.',
                    'Fold in the mushrooms and parmesan just before serving.',
                ],
                'nutritionStatus' => NutritionStatus::PENDING,
                'nutrition' => null,
            ],
            [
                'title' => 'Avocado Toast with Poached Egg',
                'notes' => 'A quick, satisfying breakfast that always hits the spot.',
                'author' => 'amelia',
                'servings' => 1,
                'cookTimeMinutes' => 10,
                'isPublic' => true,
                'tags' => ['Breakfast', 'Quick', 'Vegetarian'],
                'ingredients' => [
                    ['Sourdough bread', 1, Unit::Piece],
                    ['Avocado', 1, Unit::Piece],
                    ['Egg', 1, Unit::Piece],
                    ['Lemon juice', 1, Unit::Teaspoon],
                ],
                'steps' => [
                    'Toast the sourdough until crisp.',
                    'Mash the avocado with lemon juice, salt and pepper.',
                    'Poach the egg for about three minutes.',
                    'Spread the avocado on the toast and top with the egg.',
                ],
                'nutritionStatus' => NutritionStatus::FAILED,
                'nutrition' => null,
            ],
            [
                'title' => 'Beef Bourguignon',
                'notes' => 'Rich, slow-braised beef stew. Best made a day ahead so the flavours can settle.',
                'author' => 'marco',
                'servings' => 6,
                'cookTimeMinutes' => 180,
                'isPublic' => false,
                'tags' => ['French', 'Dinner', 'Comfort Food'],
                'ingredients' => [
                    ['Beef chuck', 1000, Unit::Gram],
                    ['Carrot', 3, Unit::Piece],
                    ['Red wine', 500, Unit::Milliliter],
                    ['Pearl onion', 200, Unit::Gram],
                ],
                'steps' => [
                    'Sear the beef chuck in batches until well browned.',
                    'Soften the carrots and onions in the same pot.',
                    'Return the beef, pour in the red wine and bring to a simmer.',
                    'Cover and braise low and slow until the meat is tender.',
                ],
                'nutritionStatus' => null,
                'nutrition' => null,
            ],
            [
                'title' => 'Matcha Pancakes',
                'notes' => 'Fluffy pancakes with a gentle earthy sweetness from the matcha.',
                'author' => 'yuki',
                'servings' => 3,
                'cookTimeMinutes' => 20,
                'isPublic' => true,
                'tags' => ['Breakfast', 'Japanese', 'Sweet'],
                'ingredients' => [
                    ['Flour', 250, Unit::Gram],
                    ['Matcha powder', 10, Unit::Gram],
                    ['Milk', 300, Unit::Milliliter],
                    ['Maple syrup', 2, Unit::Tablespoon],
                ],
                'steps' => [
                    'Whisk together the flour, matcha powder and a pinch of salt.',
                    'Stir in the milk to form a smooth batter.',
                    'Cook spoonfuls of batter on a hot griddle until golden on both sides.',
                    'Stack and serve drizzled with maple syrup.',
                ],
                'nutritionStatus' => null,
                'nutrition' => null,
            ],
            [
                'title' => 'Chicken Tikka Masala',
                'notes' => 'Marinated chicken simmered in a spiced tomato cream sauce. Serve with rice or naan.',
                'author' => 'marco',
                'servings' => 4,
                'cookTimeMinutes' => 45,
                'isPublic' => true,
                'tags' => ['Indian', 'Spicy', 'Dinner'],
                'ingredients' => [
                    ['Chicken thigh', 600, Unit::Gram],
                    ['Yogurt', 150, Unit::Gram],
                    ['Garam masala', 2, Unit::Tablespoon],
                    ['Tomato puree', 400, Unit::Gram],
                ],
                'steps' => [
                    'Marinate the chicken in yogurt and garam masala for at least an hour.',
                    'Sear the marinated chicken until lightly charred.',
                    'Simmer the tomato puree with spices until rich and thick.',
                    'Stir the chicken into the sauce and finish with a splash of cream.',
                ],
                'nutritionStatus' => NutritionStatus::DONE,
                'nutrition' => [
                    'total_calories' => 1640,
                    'calories_per_serving' => 410,
                    'breakdown' => [
                        ['ingredient' => 'Chicken thigh', 'calories' => 1080],
                        ['ingredient' => 'Yogurt', 'calories' => 120],
                        ['ingredient' => 'Tomato puree', 'calories' => 320],
                        ['ingredient' => 'Garam masala', 'calories' => 30],
                    ],
                    'confidence' => 'high',
                ],
            ],
            [
                'title' => 'Greek Salad',
                'notes' => 'A bright, crunchy salad that comes together in minutes.',
                'author' => 'amelia',
                'servings' => 2,
                'cookTimeMinutes' => 10,
                'isPublic' => true,
                'tags' => ['Vegetarian', 'Quick', 'Salad'],
                'ingredients' => [
                    ['Cucumber', 1, Unit::Piece],
                    ['Feta', 100, Unit::Gram],
                    ['Olives', 50, Unit::Gram],
                    ['Red onion', 1, Unit::Piece],
                ],
                'steps' => [
                    'Chop the cucumber and red onion into bite-sized pieces.',
                    'Toss with olives and a generous drizzle of olive oil.',
                    'Top with crumbled feta and a pinch of oregano.',
                ],
                'nutritionStatus' => NutritionStatus::DONE,
                'nutrition' => [
                    'total_calories' => 520,
                    'calories_per_serving' => 260,
                    'breakdown' => [
                        ['ingredient' => 'Feta', 'calories' => 270],
                        ['ingredient' => 'Olives', 'calories' => 115],
                        ['ingredient' => 'Cucumber', 'calories' => 30],
                        ['ingredient' => 'Red onion', 'calories' => 40],
                    ],
                    'confidence' => 'medium',
                ],
            ],
            [
                'title' => 'Beef Tacos',
                'notes' => 'Weeknight tacos with a quick spiced beef filling.',
                'author' => 'devon',
                'servings' => 4,
                'cookTimeMinutes' => 20,
                'isPublic' => true,
                'tags' => ['Mexican', 'Spicy', 'Quick'],
                'ingredients' => [
                    ['Ground beef', 500, Unit::Gram],
                    ['Taco shells', 8, Unit::Piece],
                    ['Cheddar', 100, Unit::Gram],
                    ['Lettuce', 1, Unit::Piece],
                ],
                'steps' => [
                    'Brown the ground beef with taco seasoning.',
                    'Warm the taco shells in the oven.',
                    'Fill each shell with beef, cheddar and shredded lettuce.',
                ],
                'nutritionStatus' => NutritionStatus::PENDING,
                'nutrition' => null,
            ],
            [
                'title' => 'Tomato Basil Soup',
                'notes' => 'A creamy, comforting soup that pairs perfectly with grilled cheese.',
                'author' => 'yuki',
                'servings' => 4,
                'cookTimeMinutes' => 30,
                'isPublic' => true,
                'tags' => ['Vegetarian', 'Comfort Food', 'Soup'],
                'ingredients' => [
                    ['Canned tomatoes', 800, Unit::Gram],
                    ['Onion', 1, Unit::Piece],
                    ['Vegetable stock', 500, Unit::Milliliter],
                    ['Cream', 100, Unit::Milliliter],
                ],
                'steps' => [
                    'Soften the diced onion in a pot with a little oil.',
                    'Add the canned tomatoes and vegetable stock, then simmer for 15 minutes.',
                    'Blend until smooth and stir in the cream.',
                    'Finish with torn fresh basil before serving.',
                ],
                'nutritionStatus' => NutritionStatus::DONE,
                'nutrition' => [
                    'total_calories' => 680,
                    'calories_per_serving' => 170,
                    'breakdown' => [
                        ['ingredient' => 'Canned tomatoes', 'calories' => 200],
                        ['ingredient' => 'Cream', 'calories' => 340],
                        ['ingredient' => 'Onion', 'calories' => 40],
                        ['ingredient' => 'Vegetable stock', 'calories' => 100],
                    ],
                    'confidence' => 'medium',
                ],
            ],
            [
                'title' => 'Pad Thai',
                'notes' => 'Sweet, sour and savoury rice noodles tossed with egg and peanuts.',
                'author' => 'marco',
                'servings' => 2,
                'cookTimeMinutes' => 25,
                'isPublic' => true,
                'tags' => ['Thai', 'Dinner'],
                'ingredients' => [
                    ['Rice noodles', 200, Unit::Gram],
                    ['Bean sprouts', 100, Unit::Gram],
                    ['Peanuts', 50, Unit::Gram],
                    ['Tamarind paste', 2, Unit::Tablespoon],
                ],
                'steps' => [
                    'Soak the rice noodles in warm water until pliable.',
                    'Scramble the egg in a hot wok and push to one side.',
                    'Add the noodles, tamarind paste and fish sauce, tossing to combine.',
                    'Fold in the bean sprouts and top with crushed peanuts.',
                ],
                'nutritionStatus' => NutritionStatus::FAILED,
                'nutrition' => null,
            ],
            [
                'title' => 'Chocolate Chip Cookies',
                'notes' => 'Crisp at the edges, chewy in the middle — a classic for a reason.',
                'author' => 'amelia',
                'servings' => 12,
                'cookTimeMinutes' => 25,
                'isPublic' => true,
                'tags' => ['Sweet', 'Dessert'],
                'ingredients' => [
                    ['Butter', 200, Unit::Gram],
                    ['Brown sugar', 150, Unit::Gram],
                    ['Chocolate chips', 200, Unit::Gram],
                    ['Vanilla extract', 1, Unit::Teaspoon],
                ],
                'steps' => [
                    'Cream the butter and brown sugar until light and fluffy.',
                    'Beat in the egg and vanilla extract.',
                    'Fold in the flour and chocolate chips.',
                    'Scoop onto a tray and bake until golden at the edges.',
                ],
                'nutritionStatus' => NutritionStatus::DONE,
                'nutrition' => [
                    'total_calories' => 2640,
                    'calories_per_serving' => 220,
                    'breakdown' => [
                        ['ingredient' => 'Butter', 'calories' => 1440],
                        ['ingredient' => 'Chocolate chips', 'calories' => 1040],
                        ['ingredient' => 'Brown sugar', 'calories' => 580],
                        ['ingredient' => 'Vanilla extract', 'calories' => 10],
                    ],
                    'confidence' => 'high',
                ],
            ],
            [
                'title' => 'French Onion Soup',
                'notes' => 'Deeply caramelised onions in a rich broth, topped with melted gruyere.',
                'author' => 'devon',
                'servings' => 4,
                'cookTimeMinutes' => 75,
                'isPublic' => false,
                'tags' => ['French', 'Soup', 'Comfort Food'],
                'ingredients' => [
                    ['Yellow onion', 4, Unit::Piece],
                    ['Beef stock', 1, Unit::Liter],
                    ['Gruyere', 150, Unit::Gram],
                    ['Baguette', 1, Unit::Piece],
                ],
                'steps' => [
                    'Slowly caramelise the sliced onions over low heat for an hour.',
                    'Pour in the beef stock and simmer for 20 minutes.',
                    'Ladle into bowls, top with baguette slices and gruyere.',
                    'Broil until the cheese is bubbling and golden.',
                ],
                'nutritionStatus' => null,
                'nutrition' => null,
            ],
            [
                'title' => 'Vegan Buddha Bowl',
                'notes' => 'A balanced bowl of grains, greens and a creamy tahini dressing.',
                'author' => 'yuki',
                'servings' => 2,
                'cookTimeMinutes' => 30,
                'isPublic' => true,
                'tags' => ['Vegan', 'Vegetarian', 'Quick'],
                'ingredients' => [
                    ['Quinoa', 150, Unit::Gram],
                    ['Chickpeas', 200, Unit::Gram],
                    ['Kale', 100, Unit::Gram],
                    ['Tahini', 2, Unit::Tablespoon],
                ],
                'steps' => [
                    'Cook the quinoa according to package instructions.',
                    'Roast the chickpeas until golden and slightly crisp.',
                    'Massage the kale with a little olive oil and lemon juice.',
                    'Assemble the bowl and drizzle with a tahini dressing.',
                ],
                'nutritionStatus' => NutritionStatus::PENDING,
                'nutrition' => null,
            ],
            [
                'title' => 'Shakshuka',
                'notes' => 'Eggs poached in a spiced tomato and pepper sauce, eaten straight from the pan.',
                'author' => 'marco',
                'servings' => 2,
                'cookTimeMinutes' => 25,
                'isPublic' => true,
                'tags' => ['Breakfast', 'Spicy', 'Vegetarian'],
                'ingredients' => [
                    ['Bell pepper', 1, Unit::Piece],
                    ['Canned tomatoes', 400, Unit::Gram],
                    ['Paprika', 1, Unit::Teaspoon],
                    ['Feta', 50, Unit::Gram],
                ],
                'steps' => [
                    'Soften the diced bell pepper and onion in olive oil.',
                    'Stir in the paprika and canned tomatoes, then simmer until thickened.',
                    'Crack the eggs directly into the sauce and cover until just set.',
                    'Crumble feta over the top and serve with bread.',
                ],
                'nutritionStatus' => NutritionStatus::DONE,
                'nutrition' => [
                    'total_calories' => 540,
                    'calories_per_serving' => 270,
                    'breakdown' => [
                        ['ingredient' => 'Canned tomatoes', 'calories' => 100],
                        ['ingredient' => 'Feta', 'calories' => 135],
                        ['ingredient' => 'Bell pepper', 'calories' => 35],
                        ['ingredient' => 'Paprika', 'calories' => 5],
                    ],
                    'confidence' => 'low',
                ],
            ],
            [
                'title' => 'Classic Caesar Salad',
                'notes' => 'Crisp romaine, crunchy croutons and a punchy dressing.',
                'author' => 'amelia',
                'servings' => 2,
                'cookTimeMinutes' => 15,
                'isPublic' => true,
                'tags' => ['Salad', 'Quick'],
                'ingredients' => [
                    ['Romaine lettuce', 1, Unit::Piece],
                    ['Croutons', 80, Unit::Gram],
                    ['Parmesan', 40, Unit::Gram],
                    ['Caesar dressing', 3, Unit::Tablespoon],
                ],
                'steps' => [
                    'Tear the romaine lettuce into a large bowl.',
                    'Toss with the Caesar dressing until evenly coated.',
                    'Top with croutons and shaved parmesan.',
                ],
                'nutritionStatus' => null,
                'nutrition' => null,
            ],
        ];

        $recipes = [];
        foreach ($definitions as $definition) {
            $recipe = new Recipe();
            $recipe->setTitle($definition['title']);
            $recipe->setNotes($definition['notes']);
            $recipe->setServings($definition['servings']);
            $recipe->setCookTimeMinutes($definition['cookTimeMinutes']);
            $recipe->setIsPublic($definition['isPublic']);
            $recipe->setAuthor($users[$definition['author']]);
            $recipe->setNutritionStatus($definition['nutritionStatus']);
            $recipe->setNutrition($definition['nutrition']);

            foreach ($definition['tags'] as $tagName) {
                $recipe->addTag($tags[$tagName]);
            }

            foreach ($definition['ingredients'] as $position => [$ingredientName, $amount, $unit]) {
                $recipeIngredient = new RecipeIngredient();
                $recipeIngredient->setIngredient($ingredients[$ingredientName]);
                $recipeIngredient->setAmount($amount);
                $recipeIngredient->setUnit($unit);
                $recipeIngredient->setPosition($position);

                $recipe->addRecipeIngredient($recipeIngredient);
            }

            foreach ($definition['steps'] as $position => $instruction) {
                $step = new RecipeStep();
                $step->setInstruction($instruction);
                $step->setPosition($position);

                $recipe->addStep($step);
            }

            $manager->persist($recipe);
            $recipes[$definition['title']] = $recipe;
        }

        return $recipes;
    }

    /**
     * @param array<string, User> $users
     * @param array<string, Recipe> $recipes
     */
    private function loadCommentsAndSaves(ObjectManager $manager, array $users, array $recipes): void
    {
        $comments = [
            ['Classic Margherita Pizza', 'marco', 'Made this last night, the crust came out perfect.'],
            ['Classic Margherita Pizza', 'devon', 'Used buffalo mozzarella and it was even better.'],
            ['Spicy Thai Basil Chicken', 'amelia', 'Way too spicy for me but my partner loved it!'],
            ['Creamy Mushroom Risotto', 'devon', 'Worth every minute of stirring.'],
            ['Matcha Pancakes', 'amelia', 'My kids ask for these every weekend now.'],
            ['Chicken Tikka Masala', 'devon', 'Better than my local takeaway, honestly.'],
            ['Greek Salad', 'marco', 'Added some grilled chicken on top and it was great.'],
            ['Tomato Basil Soup', 'amelia', 'Froze the leftovers and they reheated perfectly.'],
            ['Chocolate Chip Cookies', 'devon', 'Doubled the batch, gone in a day.'],
            ['Shakshuka', 'yuki', 'Great way to use up the last of the garden tomatoes.'],
        ];

        foreach ($comments as [$recipeTitle, $username, $body]) {
            $comment = new RecipeComment();
            $comment->setRecipe($recipes[$recipeTitle]);
            $comment->setAuthor($users[$username]);
            $comment->setBody($body);

            $manager->persist($comment);

            $author = $recipes[$recipeTitle]->getAuthor();
            if ($author !== $users[$username]) {
                $notification = new Notification();
                $notification->setRecipient($author);
                $notification->setActor($users[$username]);
                $notification->setRecipe($recipes[$recipeTitle]);
                $notification->setType(Notification::TYPE_COMMENT);

                $manager->persist($notification);
            }
        }

        $saves = [
            ['devon', 'Classic Margherita Pizza'],
            ['devon', 'Spicy Thai Basil Chicken'],
            ['amelia', 'Creamy Mushroom Risotto'],
            ['marco', 'Matcha Pancakes'],
            ['yuki', 'Chicken Tikka Masala'],
            ['devon', 'Greek Salad'],
            ['amelia', 'Pad Thai'],
            ['marco', 'Vegan Buddha Bowl'],
        ];

        foreach ($saves as [$username, $recipeTitle]) {
            $recipe = $recipes[$recipeTitle];

            $saved = new RecipeSaved();
            $saved->setRecipe($recipe);
            $saved->setAcount($users[$username]);

            $manager->persist($saved);

            $notification = new Notification();
            $notification->setRecipient($recipe->getAuthor());
            $notification->setActor($users[$username]);
            $notification->setRecipe($recipe);
            $notification->setType(Notification::TYPE_SAVE);

            $manager->persist($notification);
        }
    }
}
