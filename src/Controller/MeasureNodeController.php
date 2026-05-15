<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\MeasureNode;
use App\Form\MeasureNodeType;
use App\Repository\MeasureNodeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/measure-node')]
class MeasureNodeController extends AbstractController
{
    #[Route('/{id}/edit', name: 'app_measurenode_edit', methods: ['GET', 'POST'])]
    public function edit(
        MeasureNode $measureNode,
        Request $request,
        EntityManagerInterface $em,
        MeasureNodeRepository $measureNodeRepository
    ): Response {
        $category = $measureNode->getCategory();

        $form = $this->createForm(MeasureNodeType::class, $measureNode);
        $form->handleRequest($request);

        // Soumission
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            if ($request->isXmlHttpRequest()) {
                $categoryNodes = $measureNodeRepository->findAllForCategory($category);
                $treeNodes = $this->buildMeasureTree($categoryNodes);

                $html = $this->renderView('referential/_category_nodes.html.twig', [
                    'category'  => $category,
                    'treeNodes' => $treeNodes,
                ]);

                return $this->json([
                    'status' => 'ok',
                    'html'   => $html,
                    'target' => '#category-' . $category->getId() . '-nodes',
                ]);
            }

            return $this->redirectToRoute('app_referential_show', [
                'id' => $category->getReferential()->getId(),
            ]);
        }

        // Affichage du form en Ajax
        if ($request->isXmlHttpRequest()) {
            $html = $this->renderView('measurenode/_form.html.twig', [
                'form'         => $form->createView(),
                'category'     => $category,
                'submit_label' => 'Mettre à jour',
                'action_path'  => $this->generateUrl('app_measurenode_edit', [
                    'id' => $measureNode->getId(),
                ]),
            ]);

            return $this->json([
                'status' => 'form',
                'html'   => $html,
                'title'  => 'Modifier la mesure ' . $measureNode->getCode(),
            ]);
        }

        // Fallback page complète
        return $this->render('measurenode/edit.html.twig', [
            'measure_node' => $measureNode,
            'form'         => $form,
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

    #[Route('/{id}/child/new', name: 'app_measurenode_new_child', methods: ['GET', 'POST'])]
    public function newChild(
        MeasureNode $parent,
        Request $request,
        EntityManagerInterface $em,
        MeasureNodeRepository $measureNodeRepository
    ): Response {
        $category = $parent->getCategory();

        $node = new MeasureNode();
        $node->setParent($parent);
        $node->setCategory($category);
        $node->setActive(true);
        $node->setOrdre(0);

        $form = $this->createForm(MeasureNodeType::class, $node);
        $form->handleRequest($request);

        // Soumission
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($node);
            $em->flush();

            if ($request->isXmlHttpRequest()) {
                $categoryNodes = $measureNodeRepository->findAllForCategory($category);
                $treeNodes = $this->buildMeasureTree($categoryNodes);

                $html = $this->renderView('referential/_category_nodes.html.twig', [
                    'category'  => $category,
                    'treeNodes' => $treeNodes,
                ]);

                return $this->json([
                    'status' => 'ok',
                    'html'   => $html,
                    'target' => '#category-' . $category->getId() . '-nodes',
                ]);
            }

            return $this->redirectToRoute('app_referential_show', [
                'id' => $category->getReferential()->getId(),
            ]);
        }

        // Affichage du form en Ajax
        if ($request->isXmlHttpRequest()) {
            $html = $this->renderView('measurenode/_form.html.twig', [
                'form'         => $form->createView(),
                'category'     => $category,
                'submit_label' => 'Enregistrer',
                'action_path'  => $this->generateUrl('app_measurenode_new_child', [
                    'id' => $parent->getId(),
                ]),
            ]);

            return $this->json([
                'status' => 'form',
                'html'   => $html,
                'title'  => 'Nouvelle sous-mesure de ' . $parent->getCode(),
            ]);
        }

        // Fallback page complète
        return $this->render('measurenode/new_child.html.twig', [
            'parent'   => $parent,
            'category' => $category,
            'form'     => $form,
        ]);
    }

    #[Route('/new/{id}', name: 'app_measurenode_new_for_category', methods: ['GET', 'POST'])]
    public function newForCategory(
        Category $category,
        Request $request,
        EntityManagerInterface $em,
        MeasureNodeRepository $measureNodeRepository
    ): Response {
        $node = new MeasureNode();
        $node->setCategory($category);
        $node->setActive(true);
        $node->setOrdre(0);

        $form = $this->createForm(MeasureNodeType::class, $node);
        $form->handleRequest($request);

        // 1) Soumission du formulaire
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($node);
            $em->flush();

            // 1.a) Réponse AJAX
            if ($request->isXmlHttpRequest()) {
                $categoryNodes = $measureNodeRepository->findAllForCategory($category);
                $treeNodes = $this->buildMeasureTree($categoryNodes);

                $html = $this->renderView('referential/_category_nodes.html.twig', [
                    'category'  => $category,
                    'treeNodes' => $treeNodes,
                ]);

                return $this->json([
                    'status' => 'ok',
                    'html'   => $html,
                    'target' => '#category-' . $category->getId() . '-nodes',
                ]);
            }

            // 1.b) Fallback non-AJAX
            return $this->redirectToRoute('app_referential_show', [
                'id' => $category->getReferential()->getId(),
            ]);
        }

        // 2) Affichage initial du formulaire
        if ($request->isXmlHttpRequest()) {
            $html = $this->renderView('measurenode/_form.html.twig', [
                'form'         => $form->createView(),
                'submit_label' => 'Enregistrer',
                'category'     => $category,
                'action_path'  => $this->generateUrl('app_measurenode_new_for_category', [
                    'id' => $category->getId(),
                ]),
            ]);

            return $this->json([
                'status' => 'form',
                'html'   => $html,
                'title'  => 'Nouvelle mesure - ' . $category->getName(),
            ]);
        }

        // 3) Fallback page complète
        return $this->render('measurenode/new.html.twig', [
            'category' => $category,
            'node'     => $node,
            'form'     => $form,
        ]);
    }
}
