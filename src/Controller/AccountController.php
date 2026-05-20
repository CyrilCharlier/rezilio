<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/account', name: 'account_')]
class AccountController extends AbstractController
{
    #[Route('/profile', name: 'profile', methods: ['GET'])]
    public function profile(): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('account/profile.html.twig', [
            'user' => $user,
        ]);
    }
}
