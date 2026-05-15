<?php

namespace App\Controller;

use App\Entity\Society;
use App\Form\SocietyType;
use App\Repository\SocietyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/society')]
final class SocietyController extends AbstractController
{
    #[Route(name: 'app_society_index', methods: ['GET'])]
    public function index(SocietyRepository $societyRepository): Response
    {
        return $this->render('society/index.html.twig', [
            'societies' => $societyRepository->findBy([], ['name' => 'ASC']),
        ]);
    }

    #[Route('/new', name: 'app_society_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $society = new Society();
        $form = $this->createForm(SocietyType::class, $society);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($society);
            $entityManager->flush();

            $this->addFlash('success', 'La société a été créée avec succès.');

            return $this->redirectToRoute('app_society_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('society/new.html.twig', [
            'society' => $society,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_society_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Society $society, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SocietyType::class, $society);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'La société a été modifiée avec succès.');

            return $this->redirectToRoute('app_society_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('society/edit.html.twig', [
            'society' => $society,
            'form' => $form,
        ]);
    }
}
