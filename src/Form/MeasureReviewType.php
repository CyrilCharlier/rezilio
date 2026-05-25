<?php

namespace App\Form;

use App\Entity\MeasureReview;
use App\Enum\MeasureReviewStatus;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class MeasureReviewType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('status', EnumType::class, [
                'class' => MeasureReviewStatus::class,
                'choice_label' => static fn (MeasureReviewStatus $status) => match ($status) {
                    MeasureReviewStatus::NON_COMPLIANT => 'À qualifier',
                    MeasureReviewStatus::IN_PROGRESS => 'En cours',
                    MeasureReviewStatus::BLOCKED => 'Bloquée',
                    MeasureReviewStatus::COMPLIANT => 'Conforme / validée',
                },
                'label' => 'Statut',
                'attr' => [
                    'class' => 'form-select rz-form-select',
                ],
            ])
            ->add('explanation', TextareaType::class, [
                'label' => 'Explication',
                'required' => false,
                'attr' => [
                    'class' => 'form-control rz-form-control rz-textarea',
                    'rows' => 6,
                    'placeholder' => 'Ajoutez une explication de revue…',
                ],
            ])
            ->add('proofUrl', TextType::class, [
                'label' => 'Lien de preuve',
                'required' => false,
                'attr' => [
                    'class' => 'form-control rz-form-control',
                    'placeholder' => 'https://…',
                ],
            ])
            ->add('notApplicableReason', TextareaType::class, [
                'label' => 'Motif de non-applicabilité',
                'required' => false,
                'attr' => [
                    'class' => 'form-control rz-form-control rz-textarea',
                    'rows' => 4,
                    'placeholder' => 'Expliquez pourquoi cette mesure n’est pas applicable…',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MeasureReview::class,
        ]);
    }
}
