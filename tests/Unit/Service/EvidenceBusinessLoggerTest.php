<?php

namespace App\Tests\Unit\Service;

use App\Entity\Evidence;
use App\Entity\MeasureNode;
use App\Entity\MeasureReview;
use App\Entity\User;
use App\Service\EvidenceBusinessLogger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class EvidenceBusinessLoggerTest extends TestCase
{
    private function setPrivateProperty(object $object, string $property, mixed $value): void
    {
        $reflection = new ReflectionClass($object);
        $propertyReflection = $reflection->getProperty($property);
        $propertyReflection->setValue($object, $value);
    }

    public function testLogUploadSuccessWritesStructuredInfoLog(): void
    {
        $request = Request::create('/evidences/upload', 'POST', [], [], [], [
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_USER_AGENT' => 'PHPUnit Browser',
        ]);
        $request->attributes->set('_route', 'app_evidence_upload');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $logger = $this->createMock(LoggerInterface::class);

        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(99);
        $user->method('getUserIdentifier')->willReturn('ccharlier@example.test');

        $measure = new MeasureNode();
        $measure->setCode('GV-1');
        $measure->setLabel('Politique de sécurité');

        $review = $this->createMock(MeasureReview::class);
        $review->method('getId')->willReturn(12);
        $review->method('getMeasure')->willReturn($measure);

        $evidence = $this->createMock(Evidence::class);
        $evidence->method('getId')->willReturn(55);
        $evidence->method('getOriginalFilename')->willReturn('preuve.pdf');
        $evidence->method('getStoredFilename')->willReturn('abc123.pdf');
        $evidence->method('getMimeType')->willReturn('application/pdf');
        $evidence->method('getFileSize')->willReturn(2048);
        $evidence->method('getMeasureReview')->willReturn($review);

        $logger
            ->expects($this->once())
            ->method('info')
            ->with(
                'evidence_event',
                $this->callback(function (array $context): bool {
                    $this->assertSame('evidence.upload.success', $context['event_type']);

                    $this->assertSame(55, $context['evidence']['id']);
                    $this->assertSame('preuve.pdf', $context['evidence']['original_filename']);
                    $this->assertSame('abc123.pdf', $context['evidence']['stored_filename']);
                    $this->assertSame('application/pdf', $context['evidence']['mime_type']);
                    $this->assertSame(2048, $context['evidence']['file_size']);

                    $this->assertSame('127.0.0.1', $context['context']['ip']);
                    $this->assertSame('PHPUnit Browser', $context['context']['user_agent']);
                    $this->assertSame('app_evidence_upload', $context['context']['route']);
                    $this->assertSame('POST', $context['context']['method']);

                    $this->assertSame(99, $context['meta']['initiator']['id']);
                    $this->assertSame('ccharlier@example.test', $context['meta']['initiator']['username']);
                    $this->assertSame('user', $context['meta']['initiator_type']);

                    $this->assertSame(12, $context['meta']['measure_review']['id']);
                    $this->assertNull($context['meta']['measure']['id']);
                    $this->assertSame('GV-1', $context['meta']['measure']['code']);
                    $this->assertSame('Politique de sécurité', $context['meta']['measure']['label']);

                    return true;
                })
            );

        $service = new EvidenceBusinessLogger($logger, $requestStack);
        $service->logUploadSuccess($evidence, $user);
    }

    public function testLogAccessDeniedWritesWarningWithReason(): void
    {
        $request = Request::create('/evidences/12', 'GET', [], [], [], [
            'REMOTE_ADDR' => '10.0.0.5',
            'HTTP_USER_AGENT' => 'Security Audit Bot',
        ]);
        $request->attributes->set('_route', 'app_evidence_download');

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $logger = $this->createMock(LoggerInterface::class);

        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(7);
        $user->method('getUserIdentifier')->willReturn('auditor@example.test');

        $measure = new MeasureNode();
        $this->setPrivateProperty($measure, 'id', 33);
        $measure->setCode('PR-2');
        $measure->setLabel('Gestion des accès');

        $review = $this->createMock(MeasureReview::class);
        $review->method('getId')->willReturn(44);
        $review->method('getMeasure')->willReturn($measure);

        $logger
            ->expects($this->once())
            ->method('warning')
            ->with(
                'evidence_event',
                $this->callback(function (array $context): bool {
                    $this->assertSame('evidence.access.denied', $context['event_type']);
                    $this->assertNull($context['evidence']);

                    $this->assertSame('10.0.0.5', $context['context']['ip']);
                    $this->assertSame('Security Audit Bot', $context['context']['user_agent']);
                    $this->assertSame('app_evidence_download', $context['context']['route']);
                    $this->assertSame('GET', $context['context']['method']);

                    $this->assertSame(7, $context['meta']['initiator']['id']);
                    $this->assertSame('auditor@example.test', $context['meta']['initiator']['username']);
                    $this->assertSame('user', $context['meta']['initiator_type']);

                    $this->assertSame('forbidden_measure_scope', $context['meta']['reason']);
                    $this->assertSame(44, $context['meta']['measure_review']['id']);
                    $this->assertSame(33, $context['meta']['measure']['id']);
                    $this->assertSame('PR-2', $context['meta']['measure']['code']);
                    $this->assertSame('Gestion des accès', $context['meta']['measure']['label']);

                    return true;
                })
            );

        $service = new EvidenceBusinessLogger($logger, $requestStack);
        $service->logAccessDenied($review, $user, 'forbidden_measure_scope');
    }

    public function testLogDeleteCsrfInvalidAddsReasonCode(): void
    {
        $requestStack = new RequestStack();
        $logger = $this->createMock(LoggerInterface::class);

        $evidence = $this->createMock(Evidence::class);
        $evidence->method('getId')->willReturn(1);
        $evidence->method('getOriginalFilename')->willReturn('preuve.docx');
        $evidence->method('getStoredFilename')->willReturn('stored.docx');
        $evidence->method('getMimeType')->willReturn('application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $evidence->method('getFileSize')->willReturn(1024);
        $evidence->method('getMeasureReview')->willReturn(null);

        $logger
            ->expects($this->once())
            ->method('warning')
            ->with(
                'evidence_event',
                $this->callback(function (array $context): bool {
                    $this->assertSame('evidence.delete.csrf_invalid', $context['event_type']);

                    $this->assertArrayHasKey('context', $context);
                    $this->assertSame([
                        'ip' => null,
                        'user_agent' => null,
                        'route' => null,
                        'method' => null,
                    ], $context['context']);

                    $this->assertSame('csrf_token_invalid', $context['meta']['reason']);
                    $this->assertSame('csrf_invalid', $context['meta']['reason_code']);

                    return true;
                })
            );

        $service = new EvidenceBusinessLogger($logger, $requestStack);
        $service->logDeleteCsrfInvalid($evidence, null);
    }

    public function testLogDownloadFileMissingAddsReasonCode(): void
    {
        $requestStack = new RequestStack();
        $logger = $this->createMock(LoggerInterface::class);

        $evidence = $this->createMock(Evidence::class);
        $evidence->method('getId')->willReturn(2);
        $evidence->method('getOriginalFilename')->willReturn('archive.zip');
        $evidence->method('getStoredFilename')->willReturn('missing.zip');
        $evidence->method('getMimeType')->willReturn('application/zip');
        $evidence->method('getFileSize')->willReturn(4096);
        $evidence->method('getMeasureReview')->willReturn(null);

        $logger
            ->expects($this->once())
            ->method('warning')
            ->with(
                'evidence_event',
                $this->callback(function (array $context): bool {
                    $this->assertSame('evidence.download.file_missing', $context['event_type']);

                    $this->assertArrayHasKey('context', $context);
                    $this->assertSame([
                        'ip' => null,
                        'user_agent' => null,
                        'route' => null,
                        'method' => null,
                    ], $context['context']);

                    $this->assertSame('Le fichier physique associé à la preuve est introuvable.', $context['meta']['reason']);
                    $this->assertSame('file_missing', $context['meta']['reason_code']);

                    return true;
                })
            );

        $service = new EvidenceBusinessLogger($logger, $requestStack);
        $service->logDownloadFileMissing($evidence, null);
    }
}
