<?php

namespace App\Service\Campaign;

use App\Entity\Campaign;
use App\Entity\MeasureNode;
use App\Entity\MeasureReview;
use App\Enum\MeasureReviewStatus;
use App\Form\Model\CampaignCreationModel;
use App\Repository\MeasureNodeRepository;
use Doctrine\ORM\EntityManagerInterface;

class CampaignCreator
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MeasureNodeRepository $measureNodeRepository,
    ) {
    }

    public function createFromModel(CampaignCreationModel $model): Campaign
    {
        if (!$model->name || !$model->society || !$model->referential || !$model->startDate) {
            throw new \InvalidArgumentException('Les données de création de campagne sont incomplètes.');
        }

        $campaign = new Campaign();
        $campaign->setName($model->name);
        $campaign->setStartDate($model->startDate);
        $campaign->setEndDate($model->endDate);
        $campaign->setSociety($model->society);
        $campaign->setReferential($model->referential);

        $this->entityManager->persist($campaign);

        $measureNodes = $this->measureNodeRepository->findLeafNodesByReferential($model->referential);

        /** @var MeasureNode $measureNode */
        foreach ($measureNodes as $measureNode) {
            $review = new MeasureReview();
            $review->setCampaign($campaign);
            $campaign->addMeasureReview($review);
            $review->setMeasure($measureNode);

            $review->setStatus(MeasureReviewStatus::NON_COMPLIANT);

            $this->entityManager->persist($review);
        }

        $this->entityManager->flush();

        return $campaign;
    }
}
