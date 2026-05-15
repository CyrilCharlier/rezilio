<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Referential;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use App\Repository\MeasureNodeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/category')]
final class CategoryController extends AbstractController
{
    #[Route(name: 'app_category_index', methods: ['GET'])]
    public function index(CategoryRepository $categoryRepository): Response
    {
        return $this->render('category/index.html.twig', [
            'categories' => $categoryRepository->findBy([], ['ordre' => 'ASC']),
        ]);
    }

    #[Route('/new', name: 'app_category_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($category);
            $entityManager->flush();

            return $this->redirectToRoute('app_category_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('category/new.html.twig', [
            'category' => $category,
            'form' => $form,
        ]);
    }

    #[Route('/new/for-referential/{id}', name: 'app_category_new_for_referential', methods: ['GET', 'POST'])]
    public function newForReferential(
        Referential $referential,
        Request $request,
        EntityManagerInterface $em,
        CategoryRepository $categoryRepository,
        MeasureNodeRepository $measureNodeRepository
    ): Response {
        $category = new Category();
        $category->setReferential($referential);

        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        // Soumission
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($category);
            $em->flush();

            if ($request->isXmlHttpRequest()) {
                // Recharger la liste des catégories du référentiel
                $categories = $categoryRepository->findBy(
                    ['referential' => $referential],
                    ['ordre' => 'ASC'] // à adapter selon ton tri
                );

                // Récupérer aussi les nodes racine
                $rootNodes = $measureNodeRepository->findRootNodesForReferential($referential);

                $html = $this->renderView('referential/_categories.html.twig', [
                    'referential' => $referential,
                    'categories'  => $categories,
                    'rootNodes'   => $rootNodes,
                ]);

                return $this->json([
                    'status' => 'ok',
                    'html'   => $html,
                    'target' => '#referential-' . $referential->getId() . '-categories',
                ]);
            }

            return $this->redirectToRoute('app_referential_show', [
                'id' => $referential->getId(),
            ]);
        }

        // Affichage initial du formulaire (Ajax)
        if ($request->isXmlHttpRequest()) {
            $html = $this->renderView('category/_form.html.twig', [
                'form'        => $form->createView(),
                'referential' => $referential,
                'submit_label'=> 'Enregistrer',
            ]);

            return $this->json([
                'status' => 'form',
                'html'   => $html,
                'title'  => 'Nouvelle catégorie pour ' . $referential->getCode(),
            ]);
        }

        // Fallback page complète (optionnel)
        return $this->render('category/new.html.twig', [
            'category'    => $category,
            'referential' => $referential,
            'form'        => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_category_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Category $category, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_category_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('category/edit.html.twig', [
            'category' => $category,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_category_delete', methods: ['POST'])]
    public function delete(Request $request, Category $category, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$category->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($category);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_category_index', [], Response::HTTP_SEE_OTHER);
    }
}
