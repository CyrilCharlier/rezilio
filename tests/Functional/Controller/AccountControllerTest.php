<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AccountControllerTest extends WebTestCase
{
    public function testProfileRedirectsToLoginWhenAnonymous(): void
    {
        $client = static::createClient();
        $client->request('GET', '/account/profile');

        // 1) On attend bien une redirection (302)
        self::assertResponseStatusCodeSame(302);

        // 2) On suit la redirection et on vérifie qu'on arrive bien sur /login (ou ta route réelle)
        $client->followRedirect();
        self::assertSame('/login', $client->getRequest()->getPathInfo());
    }

    public function testProfileDisplaysForAuthenticatedUser(): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $user = new User();
        $user
            ->setEmail(sprintf('user_%s_%s@example.test', 'account-profile', uniqid('', true)))
            ->setPassword('test-password-hash')
            ->setRoles(['ROLE_USER'])
            ->setIsActive(true)
            ->setTotpEnabled(false);

        $entityManager->persist($user);
        $entityManager->flush();

        $client->loginUser($user);
        $client->request('GET', '/account/profile');

        self::assertResponseIsSuccessful();
    }
}
