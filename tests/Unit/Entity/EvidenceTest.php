<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Evidence;
use App\Entity\MeasureReview;
use App\Entity\User;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class EvidenceTest extends TestCase
{
    public function testInitialState(): void
    {
        $evidence = new Evidence();

        $this->assertNull($evidence->getId());
        $this->assertNull($evidence->getFile());
        $this->assertNull($evidence->getOriginalFilename());
        $this->assertNull($evidence->getStoredFilename());
        $this->assertNull($evidence->getMimeType());
        $this->assertSame(0, $evidence->getFileSize());
        $this->assertNull($evidence->getDescription());
        $this->assertNull($evidence->getMeasureReview());
        $this->assertNull($evidence->getUploadedBy());
        $this->assertNull($evidence->getCreatedAt());
        $this->assertNull($evidence->getUpdatedAt());

        $this->assertSame('0 o', $evidence->getFormattedFileSize());
        $this->assertSame('other', $evidence->getFileCategory());
    }

    public function testSettersAndGetters(): void
    {
        $evidence = new Evidence();
        $review = new MeasureReview();
        $user = $this->createMock(User::class);

        $evidence->setOriginalFilename('preuve.pdf');
        $evidence->setStoredFilename('abc123.pdf');
        $evidence->setMimeType('application/pdf');
        $evidence->setFileSize(2048);
        $evidence->setDescription('Preuve de mise en conformité');
        $evidence->setMeasureReview($review);
        $evidence->setUploadedBy($user);

        $this->assertSame('preuve.pdf', $evidence->getOriginalFilename());
        $this->assertSame('abc123.pdf', $evidence->getStoredFilename());
        $this->assertSame('application/pdf', $evidence->getMimeType());
        $this->assertSame(2048, $evidence->getFileSize());
        $this->assertSame('Preuve de mise en conformité', $evidence->getDescription());
        $this->assertSame($review, $evidence->getMeasureReview());
        $this->assertSame($user, $evidence->getUploadedBy());
    }

    public function testOnPrePersistInitializesTimestamps(): void
    {
        $evidence = new Evidence();

        $this->assertNull($evidence->getCreatedAt());
        $this->assertNull($evidence->getUpdatedAt());

        $evidence->onPrePersist();

        $this->assertNotNull($evidence->getCreatedAt());
        $this->assertNotNull($evidence->getUpdatedAt());
        $this->assertEquals($evidence->getCreatedAt(), $evidence->getUpdatedAt());
    }

    public function testOnPreUpdateRefreshesUpdatedAt(): void
    {
        $evidence = new Evidence();

        $evidence->onPrePersist();
        $firstUpdatedAt = $evidence->getUpdatedAt();

        usleep(1000);

        $evidence->onPreUpdate();

        $this->assertNotNull($evidence->getUpdatedAt());
        $this->assertNotEquals($firstUpdatedAt, $evidence->getUpdatedAt());
    }

    public function testGetFormattedFileSizeFormatsBytesKilobytesAndMegabytes(): void
    {
        $evidence = new Evidence();

        $evidence->setFileSize(999);
        $this->assertSame('999 o', $evidence->getFormattedFileSize());

        $evidence->setFileSize(1536);
        $this->assertSame('1.5 Ko', $evidence->getFormattedFileSize());

        $evidence->setFileSize(2048);
        $this->assertSame('2 Ko', $evidence->getFormattedFileSize());

        $evidence->setFileSize(1048576);
        $this->assertSame('1 Mo', $evidence->getFormattedFileSize());

        $evidence->setFileSize(1572864);
        $this->assertSame('1.5 Mo', $evidence->getFormattedFileSize());
    }

    public function testGetFileCategoryReturnsExpectedCategory(): void
    {
        $evidence = new Evidence();

        $evidence->setMimeType('application/pdf');
        $this->assertSame('pdf', $evidence->getFileCategory());

        $evidence->setMimeType('image/png');
        $this->assertSame('image', $evidence->getFileCategory());

        $evidence->setMimeType('image/jpeg');
        $this->assertSame('image', $evidence->getFileCategory());

        $evidence->setMimeType('application/msword');
        $this->assertSame('document', $evidence->getFileCategory());

        $evidence->setMimeType('application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $this->assertSame('document', $evidence->getFileCategory());

        $evidence->setMimeType('application/zip');
        $this->assertSame('other', $evidence->getFileCategory());

        $evidence->setMimeType(null);
        $this->assertSame('other', $evidence->getFileCategory());
    }

    public function testSetFileWithUploadedFileUpdatesMetadataAndUpdatedAt(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'evidence_test_');
        file_put_contents($tmpFile, 'dummy content');

        $uploadedFile = new UploadedFile(
            $tmpFile,
            'preuve.pdf',
            'application/pdf',
            null,
            true
        );

        $evidence = new Evidence();

        $this->assertNull($evidence->getUpdatedAt());

        $evidence->setFile($uploadedFile);

        $this->assertSame($uploadedFile, $evidence->getFile());
        $this->assertSame('preuve.pdf', $evidence->getOriginalFilename());
        $this->assertSame('application/pdf', $evidence->getMimeType());
        $this->assertNotNull($evidence->getUpdatedAt());
    }
}
