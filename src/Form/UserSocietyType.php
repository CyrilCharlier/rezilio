<?php
// src/Form/Admin/UserSocietyType.php

namespace App\Form;

use App\Entity\Society;
use App\Entity\UserSociety;
use App\Enum\SocietyRole;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserSocietyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('society', EntityType::class, [
                'class' => Society::class,
                'choice_label' => 'name', // adapte si ton champ s’appelle autrement
                'placeholder' => 'Choisir une société',
                'label' => 'Société',
            ])
            ->add('role', EnumType::class, [
                'class' => SocietyRole::class,
                'choice_label' => static fn (SocietyRole $role) => match ($role) {
                    SocietyRole::ADMIN  => 'Administrateur société',
                    SocietyRole::MEMBER => 'Membre',
                    SocietyRole::READER => 'Lecture seule',
                },
                'label' => 'Rôle dans la société',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserSociety::class,
        ]);
    }
}
