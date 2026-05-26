<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Campaign;
use App\Entity\MeasureReview;
use PHPUnit\Framework\TestCase;

class CampaignTest extends TestCase
{
    public function testMeasureReviewsCollectionIsInitialized(): void
    {
        $campaign = new Campaign();

        $this->assertCount(0, $campaign->getMeasureReviews());
    }

    public function testAddMeasureReviewAddsReviewAndSetsOwningSide(): void
    {
        $campaign = new Campaign();
        $review = new MeasureReview();

        $campaign->addMeasureReview($review);

        $this->assertCount(1, $campaign->getMeasureReviews());
        $this->assertTrue($campaign->getMeasureReviews()->contains($review));
        $this->assertSame($campaign, $review->getCampaign());
    }

    public function testAddMeasureReviewDoesNotDuplicateSameReview(): void
    {
        $campaign = new Campaign();
        $review = new MeasureReview();

        $campaign->addMeasureReview($review);
        $campaign->addMeasureReview($review);

        $this->assertCount(1, $campaign->getMeasureReviews());
    }

    public function testRemoveMeasureReviewRemovesReviewAndClearsOwningSide(): void
    {
        $campaign = new Campaign();
        $review = new MeasureReview();

        $campaign->addMeasureReview($review);
        $campaign->removeMeasureReview($review);

        $this->assertCount(0, $campaign->getMeasureReviews());
        $this->assertFalse($campaign->getMeasureReviews()->contains($review));
        $this->assertNull($review->getCampaign());
    }
}
