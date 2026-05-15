<?php

namespace App\Form;

use App\Entity\RemediationAction;
use App\Enum\RemediationActionPriority;
use App\Enum\RemediationActionStatus;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class RemediationActionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'rows' => 5,
                    'placeholder' => 'Décrivez l’action à mettre en œuvre…',
                ],
            ])
            ->add('priority', EnumType::class, [
                'class' => RemediationActionPriority::class,
                'label' => 'Priorité',
                'choice_label' => static fn (RemediationActionPriority $choice): string => $choice->label(),
            ])
            ->add('status', EnumType::class, [
                'class' => RemediationActionStatus::class,
                'label' => 'Statut',
                'choice_label' => static fn (RemediationActionStatus $choice): string => $choice->label(),
            ])
            ->add('dueDate', DateType::class, [
                'label' => 'Échéance',
                'required' => false,
                'widget' => 'single_text',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RemediationAction::class,
        ]);
    }
}
