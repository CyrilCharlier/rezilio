<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Enum\EventType;
use App\Form\UserType;
use App\Service\UserSecurityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/user')]
class UserController extends AbstractController
{
    #[Route('/', name: 'admin_user_list', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $users = $em->getRepository(User::class)->findAll();

        return $this->render('user/index.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/new', name: 'admin_user_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        UserSecurityLogger $userSecurityLogger
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        $isAjax = $request->isXmlHttpRequest();

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }

            $em->persist($user);
            $em->flush();

            $userSecurityLogger->logAccountEvent(
                EventType::ACCOUNT_CREATED,
                $user->getId(),
                $user->getUserIdentifier(),
                [
                    'reason' => 'manual_creation',
                ]
            );

            if ($isAjax) {
                return new JsonResponse([
                    'status'  => 'ok',
                    'message' => 'Utilisateur créé avec succès.',
                    'userId'  => $user->getId(),
                ]);
            }

            $this->addFlash('success', 'Utilisateur créé avec succès.');
            return $this->redirectToRoute('admin_user_list');
        }

        if ($isAjax && $form->isSubmitted() && !$form->isValid()) {
            return $this->jsonFormErrors($form);
        }

        // Rendu partiel pour la modale
        return $this->render('user/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_user_edit', methods: ['GET', 'POST'])]
    public function edit(
        User $user,
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        UserSecurityLogger $userSecurityLogger
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $originalEmail = $user->getUserIdentifier();

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        $isAjax = $request->isXmlHttpRequest();

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }

            $em->flush();

            $newEmail = $user->getUserIdentifier();
            $meta = ['reason' => 'manual_update'];
            if ($newEmail !== $originalEmail) {
                $meta['original_email'] = $originalEmail;
            }

            $userSecurityLogger->logAccountEvent(
                EventType::ACCOUNT_UPDATED,
                $user->getId(),
                $newEmail,
                $meta
            );

            if ($isAjax) {
                return new JsonResponse([
                    'status'  => 'ok',
                    'message' => 'Utilisateur mis à jour avec succès.',
                ]);
            }

            $this->addFlash('success', 'Utilisateur mis à jour avec succès.');
            return $this->redirectToRoute('admin_user_list');
        }

        if ($isAjax && $form->isSubmitted() && !$form->isValid()) {
            return $this->jsonFormErrors($form);
        }

        // Rendu partiel pour la modale
        return $this->render('user/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    private function jsonFormErrors($form): JsonResponse
    {
        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $origin = $error->getOrigin();
            $name   = $origin ? $origin->getName() : 'form';
            $errors[$name][] = $error->getMessage();
        }

        return new JsonResponse([
            'status' => 'error',
            'errors' => $errors,
        ], JsonResponse::HTTP_BAD_REQUEST);
    }

    #[Route('/{id}/toggle-status', name: 'admin_user_toggle_status', methods: ['POST'])]
    public function toggleStatus(
        User $user,
        Request $request,
        EntityManagerInterface $em,
        UserSecurityLogger $userSecurityLogger
    ): JsonResponse {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('toggle_user_status_'.$user->getId(), $request->request->get('_token'))) {
            return new JsonResponse([
                'status'  => 'error',
                'message' => 'Jeton CSRF invalide.',
            ], JsonResponse::HTTP_FORBIDDEN);
        }

        $newStatus = !$user->isActive();
        $user->setIsActive($newStatus);

        if ($newStatus === false) {
            // Désactivation
            $user->setDeactivatedAt(new \DateTimeImmutable('now'));
        } else {
            // Réactivation
            $user->setDeactivatedAt(null);
        }

        $em->flush();

        $eventType = $newStatus
            ? EventType::ACCOUNT_ENABLED
            : EventType::ACCOUNT_DISABLED;

        $userSecurityLogger->logAccountEvent(
            $eventType,
            $user->getId(),
            $user->getUserIdentifier(),
            [
                'reason' => $newStatus ? 'manual_enable' : 'manual_disable',
            ]
        );

        return new JsonResponse([
            'status'   => 'ok',
            'isActive' => $newStatus,
            'userId'   => $user->getId(),
            'message'  => $newStatus
                ? 'Utilisateur activé avec succès.'
                : 'Utilisateur désactivé avec succès.',
        ]);
    }
}
