<?php

namespace App\Form;

use App\Entity\User;
use App\Form\UserSocietyType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $passwordRequired = $options['password_required'];

        $passwordConstraints = [
            new Length([
                'min' => 8,
                'minMessage' => 'Le mot de passe doit contenir au moins {{ limit }} caractères.',
            ]),
        ];

        if ($passwordRequired) {
            array_unshift($passwordConstraints, new NotBlank([
                'message' => 'Le mot de passe est obligatoire.',
            ]));
        }

        $builder
            ->add('email', EmailType::class, [
                'label'    => 'Adresse e-mail',
                'required' => true,
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type'            => PasswordType::class,
                'required'        => $passwordRequired,
                'mapped'          => false,
                'invalid_message' => 'Les mots de passe ne correspondent pas.',
                'constraints'     => $passwordConstraints,
                'first_options'   => [
                    'label' => 'Mot de passe',
                    'attr'  => [
                        'autocomplete' => $passwordRequired ? 'new-password' : 'off',
                    ],
                ],
                'second_options'  => [
                    'label' => 'Confirmation du mot de passe',
                    'attr'  => [
                        'autocomplete' => $passwordRequired ? 'new-password' : 'off',
                    ],
                ],
            ])
            ->add('roles', ChoiceType::class, [
                'label'    => 'Rôles',
                'choices'  => [
                    'Utilisateur'    => 'ROLE_USER',
                    'Administrateur' => 'ROLE_ADMIN',
                ],
                'expanded' => true,
                'multiple' => true,
                'required' => true,
            ])
            ->add('userSocieties', CollectionType::class, [
                'entry_type' => UserSocietyType::class,
                'entry_options' => [
                    'label' => false,
                ],
                'allow_add'    => true,
                'allow_delete' => true,
                'by_reference' => false,
                'prototype'    => true,
                'label'        => 'Accès aux sociétés',
                'required'     => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'        => User::class,
            'password_required' => true,
        ]);

        $resolver->setAllowedTypes('password_required', 'bool');
    }
}
