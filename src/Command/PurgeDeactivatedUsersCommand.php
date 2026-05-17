<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:user:purge-deactivated',
    description: 'Supprime les utilisateurs désactivés depuis plus de X jours',
)]
class PurgeDeactivatedUsersCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'days',
                InputArgument::OPTIONAL,
                'Nombre de jours depuis la désactivation avant suppression (90)',
                90
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io   = new SymfonyStyle($input, $output);
        $days = (int) $input->getArgument('days');

        $io->title(sprintf(
            'Purge des utilisateurs désactivés depuis plus de %d jours',
            $days
        ));

        $threshold = new \DateTimeImmutable(sprintf('-%d days', $days));

        $qb = $this->em->createQueryBuilder();
        $qb
            ->select('u')
            ->from(User::class, 'u')
            ->where('u.isActive = :active')
            ->andWhere('u.deactivatedAt IS NOT NULL')
            ->andWhere('u.deactivatedAt <= :threshold')
            ->setParameter('active', false)
            ->setParameter('threshold', $threshold)
        ;

        $users = $qb->getQuery()->getResult();
        $count = \count($users);

        if ($count === 0) {
            $io->success('Aucun utilisateur à purger.');
            return Command::SUCCESS;
        }

        foreach ($users as $user) {
            $this->em->remove($user);
        }

        $this->em->flush();

        $io->success(sprintf('%d utilisateur(s) désactivé(s) purgé(s).', $count));

        return Command::SUCCESS;
    }
}
