<?php

namespace App\Controller;

use App\Entity\User;
use App\Security\TotpSecretGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Scheb\TwoFactorBundle\Model\Totp\TotpConfiguration;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/account/2fa/enable', name: 'account_2fa_enable')]
    public function enable2fa(
        Request $request,
        EntityManagerInterface $em,
        TotpSecretGenerator $secretGenerator,
        TotpAuthenticatorInterface $totpAuthenticator,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        // Si déjà activée, tu peux directement le rediriger vers l'accueil
        if ($user->isTotpEnabled()) {
            return $this->redirectToRoute('app_default');
        }

        // Générer un secret si absent
        if ($user->getTotpSecret() === null) {
            $user->setTotpSecret($secretGenerator->generateSecret());
            $em->flush();
        }

        $config = new TotpConfiguration(
            $user->getTotpSecret(),
            TotpConfiguration::ALGORITHM_SHA1,
            30,
            6
        );

        $issuer = 'Rezilio';
        $label  = rawurlencode($issuer.':'.$user->getUserIdentifier());

        $otpAuthUri = sprintf(
            'otpauth://totp/%s?secret=%s&issuer=%s&algorithm=%s&period=%d&digits=%d',
            $label,
            $config->getSecret(),
            rawurlencode($issuer),
            $config->getAlgorithm(),
            $config->getPeriod(),
            $config->getDigits()
        );

        // Traitement du formulaire de confirmation
        if ($request->isMethod('POST')) {
            $code = $request->request->get('activation_code');

            if ($code && $totpAuthenticator->checkCode($user, $code)) {
                $user->setTotpEnabled(true);
                $em->flush();

                $this->addFlash('success', 'La double authentification est maintenant activée.');
                return $this->redirectToRoute('app_default');
            }

            $this->addFlash('error', 'Code invalide, merci de réessayer.');
        }

        return $this->render('security/2fa_enable.html.twig', [
            'user'       => $user,
            'otpAuthUri' => $otpAuthUri,
        ]);
    }
}
