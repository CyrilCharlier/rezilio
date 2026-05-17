<?php

namespace App\Controller\Admin;

use App\Entity\MeasureNode;
use App\Entity\Referential;
use App\Form\ReferentialType;
use App\Repository\CategoryRepository;
use App\Repository\MeasureNodeRepository;
use App\Repository\ReferentialRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/referential')]
final class ReferentialController extends AbstractController
{
    #[Route(name: 'app_referential_index', methods: ['GET'])]
    public function index(ReferentialRepository $referentialRepository): Response
    {
        return $this->render('referential/index.html.twig', [
            'referentials' => $referentialRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_referential_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $referential = new Referential();
        $form = $this->createForm(ReferentialType::class, $referential);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($referential);
            $entityManager->flush();

            return $this->redirectToRoute('app_referential_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('referential/new.html.twig', [
            'referential' => $referential,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_referential_show', methods: ['GET'])]
    public function show(
        Referential $referential,
        CategoryRepository $categoryRepository,
        MeasureNodeRepository $measureNodeRepository
    ): Response {
        $categories = $categoryRepository->findBy(
            ['referential' => $referential],
            ['ordre' => 'ASC']
        );

        $rootNodes = $measureNodeRepository->findRootNodesForReferential($referential);
        $allNodes = $measureNodeRepository->findAllForReferential($referential);

        $treeByCategory = [];
        foreach ($categories as $category) {
            $categoryNodes = array_filter(
                $allNodes,
                fn ($node) => $node->getCategory()?->getId() === $category->getId()
            );

            $treeByCategory[$category->getId()] = $this->buildMeasureTree($categoryNodes);
        }

        return $this->render('referential/show.html.twig', [
            'referential' => $referential,
            'categories' => $categories,
            'rootNodes' => $rootNodes,
            'allNodes' => $allNodes,
            'treeByCategory' => $treeByCategory,
        ]);
    }

    private function buildMeasureTree(array $nodes, ?MeasureNode $parent = null): array
    {
        $branch = [];

        foreach ($nodes as $node) {
            if ($node->getParent() === $parent) {
                $branch[] = [
                    'node' => $node,
                    'children' => $this->buildMeasureTree($nodes, $node),
                ];
            }
        }

        return $branch;
    }

    #[Route('/{id}/edit', name: 'app_referential_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Referential $referential, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ReferentialType::class, $referential);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_referential_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('referential/edit.html.twig', [
            'referential' => $referential,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_referential_delete', methods: ['POST'])]
    public function delete(Request $request, Referential $referential, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$referential->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($referential);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_referential_index', [], Response::HTTP_SEE_OTHER);
    }
}
