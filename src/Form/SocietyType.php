<?php

namespace App\Form;

use App\Entity\Society;
use App\Repository\SocietyRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SocietyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Society|null $society */
        $society = $options['data'] ?? null;
        $currentId = $society?->getId();

        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'attr' => [
                    'placeholder' => 'Nom de la société',
                ],
            ])
            ->add('active', CheckboxType::class, [
                'label' => 'Société active',
                'required' => false,
            ])
            ->add('parent', EntityType::class, [
                'class' => Society::class,
                'label' => 'Société mère',
                'choice_label' => function (Society $society) {
                    return $society->getName();
                },
                'placeholder' => 'Aucune société mère',
                'required' => false,
                'query_builder' => function (SocietyRepository $repository) use ($currentId) {
                    $qb = $repository->createQueryBuilder('s')
                        ->orderBy('s.name', 'ASC');

                    if ($currentId !== null) {
                        $qb->andWhere('s.id != :currentId')
                           ->setParameter('currentId', $currentId);
                    }

                    return $qb;
                },
                'help' => 'Laissez vide pour une société racine.',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Society::class,
        ]);
    }
}
