<?php

namespace App\Tests\Controller;

use App\Entity\Campaign;
use App\Entity\Referential;
use App\Entity\Society;
use App\Entity\User;
use App\Entity\UserSociety;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class DashboardControllerTest extends WebTestCase
{
    public function testDashboardRedirectsToLoginWhenAnonymous(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        self::assertResponseRedirects('/login');

        $client->followRedirect();
        self::assertSame('/login', $client->getRequest()->getPathInfo());
    }

    public function testDashboardDisplaysEmptyStateForAuthenticatedUserWithoutCampaign(): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $user = new User();
        $user
            ->setEmail(sprintf('user_%s_%s@example.test', 'dashboard-empty', uniqid('', true)))
            ->setPassword('test-password-hash')
            ->setRoles(['ROLE_USER'])
            ->setIsActive(true)
            ->setTotpEnabled(false);

        $entityManager->persist($user);
        $entityManager->flush();

        $client->loginUser($user);
        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertPageTitleContains('Dashboard');
        self::assertSelectorTextContains('h1', 'Dashboard');
        self::assertSelectorTextContains('.rz-page-intro p', 'Aucune campagne accessible.');
        self::assertSelectorNotExists('select#campaign');
        self::assertSelectorTextContains('body', 'Aucune donnée de revue disponible pour cette campagne.');
        self::assertSelectorTextContains('body', 'Aucune catégorie exploitable pour cette campagne.');
        self::assertSelectorTextContains('body', 'Aucune action prioritaire pour cette campagne.');
    }

    public function testDashboardDisplaysForAuthenticatedUserWithAccessibleSociety(): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $user = new User();
        $user
            ->setEmail(sprintf('user_%s_%s@example.test', 'dashboard-society', uniqid('', true)))
            ->setPassword('test-password-hash')
            ->setRoles(['ROLE_USER'])
            ->setIsActive(true)
            ->setTotpEnabled(false);

        $society = new Society();
        $society
            ->setName(sprintf('Society %s', uniqid('', true)))
            ->setActive(true);

        $userSociety = new UserSociety();
        $userSociety
            ->setUser($user)
            ->setSociety($society);

        $entityManager->persist($user);
        $entityManager->persist($society);
        $entityManager->persist($userSociety);
        $entityManager->flush();

        $client->loginUser($user);
        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertPageTitleContains('Dashboard');
        self::assertSelectorTextContains('h1', 'Dashboard');
    }

    public function testDashboardShowsCampaignSelectorWithAccessibleCampaign(): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $user = new User();
        $user
            ->setEmail(sprintf('user_%s_%s@example.test', 'dashboard-campaign', uniqid('', true)))
            ->setPassword('test-password-hash')
            ->setRoles(['ROLE_USER'])
            ->setIsActive(true)
            ->setTotpEnabled(false);

        $society = new Society();
        $society
            ->setName(sprintf('Society %s', uniqid('', true)))
            ->setActive(true);

        $referential = new Referential();
        $referential
            ->setCode(sprintf('REF_%s', uniqid()))
            ->setLabel('NIS2')
            ->setActive(true);

        $campaign = new Campaign();
        $campaign
            ->setName('Campagne NIS2 2026')
            ->setStartDate(new \DateTimeImmutable('2026-01-01'))
            ->setReferential($referential)
            ->setSociety($society);

        $userSociety = new UserSociety();
        $userSociety
            ->setUser($user)
            ->setSociety($society);

        $entityManager->persist($user);
        $entityManager->persist($society);
        $entityManager->persist($referential);
        $entityManager->persist($campaign);
        $entityManager->persist($userSociety);
        $entityManager->flush();

        $client->loginUser($user);
        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('select#campaign');
        self::assertAnySelectorTextContains('select#campaign option', 'Campagne NIS2 2026');
        self::assertSelectorTextContains('.rz-page-intro p', 'Campagne NIS2 2026');
        self::assertSelectorTextContains('.rz-page-intro p', 'NIS2');
        self::assertSelectorTextContains('.rz-page-intro p', $society->getName());
    }

    public function testDashboardSelectsCampaignFromQueryString(): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $user = new User();
        $user
            ->setEmail(sprintf('user_%s_%s@example.test', 'dashboard-query', uniqid('', true)))
            ->setPassword('test-password-hash')
            ->setRoles(['ROLE_USER'])
            ->setIsActive(true)
            ->setTotpEnabled(false);

        $society = new Society();
        $society
            ->setName(sprintf('Society %s', uniqid('', true)))
            ->setActive(true);

        $referential = new Referential();
        $referential
            ->setCode(sprintf('REF_%s', uniqid()))
            ->setLabel('NIS2')
            ->setActive(true);

        $campaign1 = new Campaign();
        $campaign1
            ->setName('Campagne NIS2 2025')
            ->setStartDate(new \DateTimeImmutable('2025-01-01'))
            ->setReferential($referential)
            ->setSociety($society);

        $campaign2 = new Campaign();
        $campaign2
            ->setName('Campagne NIS2 2026')
            ->setStartDate(new \DateTimeImmutable('2026-01-01'))
            ->setReferential($referential)
            ->setSociety($society);

        $userSociety = new UserSociety();
        $userSociety
            ->setUser($user)
            ->setSociety($society);

        $entityManager->persist($user);
        $entityManager->persist($society);
        $entityManager->persist($referential);
        $entityManager->persist($campaign1);
        $entityManager->persist($campaign2);
        $entityManager->persist($userSociety);
        $entityManager->flush();

        $client->loginUser($user);
        $client->request('GET', sprintf('/?campaign=%d', $campaign2->getId()));

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.rz-page-intro p', 'Campagne NIS2 2026');

        $crawler = $client->getCrawler();
        $selectedOption = $crawler->filter('select#campaign option[selected]');

        self::assertCount(1, $selectedOption);
        self::assertSame((string) $campaign2->getId(), $selectedOption->attr('value'));
    }

    public function testDashboardFallsBackToFirstAccessibleCampaignWhenRequestedCampaignIsNotAccessible(): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $user = new User();
        $user
            ->setEmail(sprintf('user_%s_%s@example.test', 'dashboard-fallback', uniqid('', true)))
            ->setPassword('test-password-hash')
            ->setRoles(['ROLE_USER'])
            ->setIsActive(true)
            ->setTotpEnabled(false);

        $accessibleSociety = new Society();
        $accessibleSociety
            ->setName(sprintf('Accessible Society %s', uniqid('', true)))
            ->setActive(true);

        $otherSociety = new Society();
        $otherSociety
            ->setName(sprintf('Other Society %s', uniqid('', true)))
            ->setActive(true);

        $referential = new Referential();
        $referential
            ->setCode(sprintf('REF_%s', uniqid()))
            ->setLabel('NIS2')
            ->setActive(true);

        $accessibleCampaign = new Campaign();
        $accessibleCampaign
            ->setName('Campagne accessible 2026')
            ->setStartDate(new \DateTimeImmutable('2026-01-01'))
            ->setReferential($referential)
            ->setSociety($accessibleSociety);

        $inaccessibleCampaign = new Campaign();
        $inaccessibleCampaign
            ->setName('Campagne inaccessible 2026')
            ->setStartDate(new \DateTimeImmutable('2026-02-01'))
            ->setReferential($referential)
            ->setSociety($otherSociety);

        $userSociety = new UserSociety();
        $userSociety
            ->setUser($user)
            ->setSociety($accessibleSociety);

        $entityManager->persist($user);
        $entityManager->persist($accessibleSociety);
        $entityManager->persist($otherSociety);
        $entityManager->persist($referential);
        $entityManager->persist($accessibleCampaign);
        $entityManager->persist($inaccessibleCampaign);
        $entityManager->persist($userSociety);
        $entityManager->flush();

        $client->loginUser($user);
        $client->request('GET', sprintf('/?campaign=%d', $inaccessibleCampaign->getId()));

        self::assertResponseIsSuccessful();

        self::assertSelectorTextContains('.rz-page-intro p', 'Campagne accessible 2026');
        self::assertSelectorTextContains('.rz-page-intro p', 'NIS2');
        self::assertSelectorTextContains('.rz-page-intro p', $accessibleSociety->getName());

        $crawler = $client->getCrawler();
        $selectedOption = $crawler->filter('select#campaign option[selected]');

        self::assertCount(1, $selectedOption);
        self::assertSame((string) $accessibleCampaign->getId(), $selectedOption->attr('value'));

        self::assertSame(
            0,
            $crawler->filter('select#campaign option[value="'.$inaccessibleCampaign->getId().'"]')->count()
        );
    }

    public function testDashboardAdminSeesCampaignsFromAllSocieties(): void
    {
        $client = static::createClient();

        /** @var EntityManagerInterface $entityManager */
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $admin = new User();
        $admin
            ->setEmail(sprintf('user_%s_%s@example.test', 'dashboard-admin', uniqid('', true)))
            ->setPassword('test-password-hash')
            ->setRoles(['ROLE_ADMIN'])
            ->setIsActive(true)
            ->setTotpEnabled(false);

        $society1 = new Society();
        $society1
            ->setName(sprintf('Admin Society A %s', uniqid('', true)))
            ->setActive(true);

        $society2 = new Society();
        $society2
            ->setName(sprintf('Admin Society B %s', uniqid('', true)))
            ->setActive(true);

        $referential = new Referential();
        $referential
            ->setCode(sprintf('REF_%s', uniqid()))
            ->setLabel('NIS2')
            ->setActive(true);

        $campaign1 = new Campaign();
        $campaign1
            ->setName('Campagne Admin A')
            ->setStartDate(new \DateTimeImmutable('2026-01-01'))
            ->setReferential($referential)
            ->setSociety($society1);

        $campaign2 = new Campaign();
        $campaign2
            ->setName('Campagne Admin B')
            ->setStartDate(new \DateTimeImmutable('2026-02-01'))
            ->setReferential($referential)
            ->setSociety($society2);

        $entityManager->persist($admin);
        $entityManager->persist($society1);
        $entityManager->persist($society2);
        $entityManager->persist($referential);
        $entityManager->persist($campaign1);
        $entityManager->persist($campaign2);
        $entityManager->flush();

        $client->loginUser($admin);
        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('select#campaign');
        self::assertAnySelectorTextContains('select#campaign option', 'Campagne Admin A');
        self::assertAnySelectorTextContains('select#campaign option', 'Campagne Admin B');
        self::assertAnySelectorTextContains('select#campaign option', $society1->getName());
        self::assertAnySelectorTextContains('select#campaign option', $society2->getName());
    }
}
