<?php

namespace App\Form\Campaign;

use App\Entity\Referential;
use App\Entity\Society;
use App\Form\Model\CampaignCreationModel;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CampaignContextType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de la campagne',
            ])
            ->add('society', EntityType::class, [
                'class' => Society::class,
                'choice_label' => 'name',
                'label' => 'Société',
                'placeholder' => 'Choisir une société',
            ])
            ->add('referential', EntityType::class, [
                'class' => Referential::class,
                'choice_label' => 'label',
                'label' => 'Référentiel',
                'placeholder' => 'Choisir un référentiel',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CampaignCreationModel::class,
            'validation_groups' => ['context'],
            'csrf_token_id' => 'campaign_context',
        ]);
    }
}
