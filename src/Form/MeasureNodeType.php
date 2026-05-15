<?php
// src/Form/MeasureNodeType.php

namespace App\Form;

use App\Entity\MeasureNode;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MeasureNodeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class, [
                'label' => 'Code',
            ])
            ->add('label', TextType::class, [
                'label' => 'Libellé',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
            ])
            ->add('ordre', IntegerType::class, [
                'label' => 'Ordre',
            ])
            ->add('active', CheckboxType::class, [
                'label' => 'Actif',
                'required' => false,
            ])
            ->add('appliesToEE', CheckboxType::class, [
                'label'    => 'EE',
                'required' => false,
            ])
            ->add('appliesToEI', CheckboxType::class, [
                'label'    => 'EI',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MeasureNode::class,
        ]);
    }
}
