<?php

namespace App\Form\Campaign;

use App\Form\Model\CampaignCreationModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CampaignScheduleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('startDate', DateType::class, [
                'label' => 'Date de début',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ])
            ->add('endDate', DateType::class, [
                'label' => 'Date de fin',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'required' => false,
            ]);

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event): void {
            /** @var CampaignCreationModel $data */
            $data = $event->getData();
            $form = $event->getForm();

            if ($data->startDate && $data->endDate && $data->endDate < $data->startDate) {
                $form->get('endDate')->addError(
                    new FormError('La date de fin doit être postérieure ou égale à la date de début.')
                );
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CampaignCreationModel::class,
            'validation_groups' => ['schedule'],
            'csrf_token_id' => 'campaign_schedule',
        ]);
    }
}
