<?php

namespace App\Tests\Functional\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Scheb\TwoFactorBundle\Security\TwoFactor\Provider\Totp\TotpAuthenticatorInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SecurityControllerTest extends WebTestCase
{
    private function createUser(
        EntityManagerInterface $em,
        string $suffix,
        bool $totpEnabled = false,
        ?string $totpSecret = null
    ): User {
        $user = new User();
        $user->setEmail(sprintf('user_%s_%s@example.test', $suffix, uniqid('', true)));
        $user->setRoles(['ROLE_USER']);
        $user->setPassword('test-password-hash');
        $user->setTotpEnabled($totpEnabled);
        $user->setTotpSecret($totpSecret);

        $em->persist($user);
        $em->flush();

        return $user;
    }

    public function testEnable2faRedirectsWhenAlreadyEnabled(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $user = $this->createUser(
            $em,
            'already_enabled',
            true,
            'ABCDEFGHIJKLMNOPQRSTUVWX234567'
        );

        $client->loginUser($user);
        $client->request('GET', '/account/2fa/enable');

        $this->assertResponseRedirects('/');
    }

    public function testEnable2faGeneratesSecretAndDisplaysPageWhenMissing(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);

        $user = $this->createUser(
            $em,
            '2fa_missing',
            false,
            null
        );

        $client->loginUser($user);
        $client->request('GET', '/account/2fa/enable');

        $this->assertResponseIsSuccessful();

        $em->clear();
        $reloadedUser = $em->getRepository(User::class)->find($user->getId());

        $this->assertNotNull($reloadedUser);
        $this->assertNotNull($reloadedUser->getTotpSecret());
        $this->assertMatchesRegularExpression('/^[A-Z2-7]{32}$/', $reloadedUser->getTotpSecret());
        $this->assertSelectorExists('body');
    }

    public function testEnable2faPostWithValidCodeEnablesTotpAndRedirects(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        $em = static::getContainer()->get(EntityManagerInterface::class);

        $user = $this->createUser(
            $em,
            'valid_code',
            false,
            'ABCDEFGHIJKLMNOPQRSTUVWX234567'
        );

        $mock = $this->createMock(TotpAuthenticatorInterface::class);
        $mock
            ->expects($this->once())
            ->method('checkCode')
            ->with(
                $this->callback(fn ($testedUser) => $testedUser instanceof User && $testedUser->getId() === $user->getId()),
                '123456'
            )
            ->willReturn(true);

        static::getContainer()->set(TotpAuthenticatorInterface::class, $mock);

        $client->loginUser($user);
        $client->request('POST', '/account/2fa/enable', [
            'activation_code' => '123456',
        ]);

        $this->assertResponseRedirects('/');

        $em->clear();
        $reloadedUser = $em->getRepository(User::class)->find($user->getId());

        $this->assertNotNull($reloadedUser);
        $this->assertTrue($reloadedUser->isTotpEnabled());
    }

    public function testEnable2faPostWithInvalidCodeKeepsTotpDisabled(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        $em = static::getContainer()->get(EntityManagerInterface::class);

        $user = $this->createUser(
            $em,
            'invalid_code',
            false,
            'ABCDEFGHIJKLMNOPQRSTUVWX234567'
        );

        $mock = $this->createMock(TotpAuthenticatorInterface::class);
        $mock
            ->expects($this->once())
            ->method('checkCode')
            ->with(
                $this->callback(fn ($testedUser) => $testedUser instanceof User && $testedUser->getId() === $user->getId()),
                '000000'
            )
            ->willReturn(false);

        static::getContainer()->set(TotpAuthenticatorInterface::class, $mock);

        $client->loginUser($user);
        $client->request('POST', '/account/2fa/enable', [
            'activation_code' => '000000',
        ]);

        $this->assertResponseIsSuccessful();

        $em->clear();
        $reloadedUser = $em->getRepository(User::class)->find($user->getId());

        $this->assertNotNull($reloadedUser);
        $this->assertFalse($reloadedUser->isTotpEnabled());
        $this->assertSelectorExists('body');
    }
}
