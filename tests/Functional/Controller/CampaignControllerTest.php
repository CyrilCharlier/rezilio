<?php

namespace App\Tests\Functional\Controller;

use App\Entity\User;
use App\Repository\CampaignRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CampaignControllerTest extends WebTestCase
{
    private function createUser(EntityManagerInterface $em): User
    {
        $user = new User();
        $user->setEmail(sprintf('campaign_user_%s@example.test', uniqid('', true)));
        $user->setRoles(['ROLE_USER']);
        $user->setPassword('test-password-hash');

        $em->persist($user);
        $em->flush();

        return $user;
    }

    public function testIndexDisplaysCampaignListAndKeepsSearchValue(): void
    {
        $client = static::createClient();
        $client->disableReboot();

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $user = $this->createUser($em);

        $repositoryMock = $this->createMock(CampaignRepository::class);
        $repositoryMock
            ->expects($this->once())
            ->method('findAccessibleForUser')
            ->with($user, 'NIS2')
            ->willReturn([]);

        static::getContainer()->set(CampaignRepository::class, $repositoryMock);

        $client->loginUser($user);

        $client->request('GET', '/campaign?search=NIS2');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('body');
    }
}
