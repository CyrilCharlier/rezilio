<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class HealthController
{
    #[Route('/healthz', name: 'app_healthz', methods: ['GET'])]
    public function __invoke(EntityManagerInterface $em): Response
    {
        try {
            $em->getConnection()->executeQuery('SELECT 1')->fetchOne();
        } catch (\Throwable $e) {
            return new Response('DB ERROR', Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return new Response('OK', Response::HTTP_OK);
    }
}