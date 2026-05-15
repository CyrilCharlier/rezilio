<?php

namespace App\Form\Model;

use App\Entity\Referential;
use App\Entity\Society;
use Symfony\Component\Validator\Constraints as Assert;

class CampaignCreationModel
{
    #[Assert\NotBlank(message: 'Le nom de la campagne est obligatoire.', groups: ['context'])]
    #[Assert\Length(max: 100, groups: ['context'])]
    public ?string $name = null;

    #[Assert\NotNull(message: 'La société est obligatoire.', groups: ['context'])]
    public ?Society $society = null;

    #[Assert\NotNull(message: 'Le référentiel est obligatoire.', groups: ['context'])]
    public ?Referential $referential = null;

    #[Assert\NotNull(message: 'La date de début est obligatoire.', groups: ['schedule'])]
    public ?\DateTimeImmutable $startDate = null;

    public ?\DateTimeImmutable $endDate = null;
}
