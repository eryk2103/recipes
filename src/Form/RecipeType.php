<?php

namespace App\Form;

use App\Dto\CreateRecipeDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RecipeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'attr' => ['class' => 'title-input', 'placeholder' => 'Recipe title'],
            ])
            ->add('notes', TextareaType::class, [
                'attr' => [
                    'class' => 'textarea',
                    'placeholder' => 'Why you love it, what to serve alongside, the one tip that makes it…',
                ],
            ])
            ->add('servings', IntegerType::class, [
                'attr' => ['class' => 'input input--mono'],
            ])
            ->add('cookTimeMinutes', IntegerType::class, [
                'required' => false,
                'attr' => ['class' => 'input input--mono', 'placeholder' => '25'],
            ])
            ->add('recipeIngredients', CollectionType::class, [
                'entry_type' => RecipeIngredientType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => false,
            ])
            ->add('steps', CollectionType::class, [
                'entry_type' => RecipeStepType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CreateRecipeDto::class,
        ]);
    }
}
