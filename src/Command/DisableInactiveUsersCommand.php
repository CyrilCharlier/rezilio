<?php

namespace App\Command;

use App\Entity\User;
use App\Enum\EventType;
use App\Service\UserSecurityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:user:disable-inactive',
    description: 'Désactive les utilisateurs inactifs depuis plus de X jours',
)]
class DisableInactiveUsersCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserSecurityLogger $userSecurityLogger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'days',
                InputArgument::OPTIONAL,
                'Nombre de jours d’inactivité avant désactivation',
                90
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io   = new SymfonyStyle($input, $output);
        $days = (int) $input->getArgument('days');

        $io->title(sprintf(
            'Désactivation des utilisateurs inactifs depuis plus de %d jours',
            $days
        ));

        $threshold = new \DateTimeImmutable(sprintf('-%d days', $days));

        $qb = $this->em->createQueryBuilder();
        $qb
            ->select('u')
            ->from(User::class, 'u')
            ->where('u.isActive = :active')
            ->andWhere(
                $qb->expr()->orX(
                    'u.lastLoginAt IS NULL',
                    'u.lastLoginAt <= :threshold'
                )
            )
            ->setParameter('active', true)
            ->setParameter('threshold', $threshold)
        ;

        /** @var User[] $users */
        $users = $qb->getQuery()->getResult();
        $count = \count($users);

        if ($count === 0) {
            $io->success('Aucun utilisateur inactif à désactiver.');
            return Command::SUCCESS;
        }

        $now = new \DateTimeImmutable('now');

        foreach ($users as $user) {
            $user->setIsActive(false);
            $user->setDeactivatedAt($now);

            $this->userSecurityLogger->logAccountEvent(
                EventType::ACCOUNT_DISABLED,
                $user->getId(),
                $user->getUserIdentifier(),
                [
                    'reason' => 'auto_disable_inactive',
                ]
            );
        }

        $this->em->flush();

        $io->success(sprintf('%d utilisateur(s) désactivé(s) pour inactivité.', $count));

        return Command::SUCCESS;
    }
}
