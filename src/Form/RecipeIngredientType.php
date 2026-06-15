<?php

namespace App\Form;

use App\Dto\CreateRecipeIngredientDto;
use App\Enum\Unit;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RecipeIngredientType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('position', HiddenType::class)
            ->add('amount', IntegerType::class, [
                'attr' => ['class' => 'input input--mono input--amount', 'placeholder' => '200'],
            ])
            ->add('unit', EnumType::class, [
                'class' => Unit::class,
                'choice_label' => static fn (Unit $unit) => $unit->value,
                'attr' => ['class' => 'input input--mono input--unit'],
            ])
            ->add('name', TextType::class, [
                'attr' => ['class' => 'input', 'placeholder' => 'Ingredient'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CreateRecipeIngredientDto::class,
        ]);
    }
}
