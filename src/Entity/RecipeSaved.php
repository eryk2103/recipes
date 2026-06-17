<?php

namespace App\Entity;

use App\Repository\RecipeSavedRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RecipeSavedRepository::class)]
class RecipeSaved
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'saved')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Recipe $recipe = null;

    #[ORM\ManyToOne(inversedBy: 'savedRecipes')]
    private ?User $acount = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRecipe(): ?Recipe
    {
        return $this->recipe;
    }

    public function setRecipe(?Recipe $recipe): static
    {
        $this->recipe = $recipe;

        return $this;
    }

    public function getAcount(): ?User
    {
        return $this->acount;
    }

    public function setAcount(?User $acount): static
    {
        $this->acount = $acount;

        return $this;
    }
}
