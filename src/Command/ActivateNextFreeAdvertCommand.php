<?php

namespace App\Command;

use App\Entity\Eventadvert;
use App\Entity\User;
use App\Service\HelperService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'promotip:activate-next-free-advert',
    description: 'Add a short description for your command',
)]
class ActivateNextFreeAdvertCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly HelperService          $helperService
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $rsm = new ResultSetMappingBuilder($this->entityManager);
        $rsm->addRootEntityFromClassMetadata(Eventadvert::class, 'eventadvert');

        $adverts = $this->entityManager->createNativeQuery('
            SELECT a.*
FROM eventadvert a
JOIN (
    SELECT b.user_id, MIN(b.id) AS next_free_ad_id
    FROM eventadvert b
    WHERE b.status = 0 
      AND b.id > COALESCE(
          (SELECT MAX(c.id) FROM eventadvert c WHERE c.status = 1 AND c.user_id = b.user_id),
          0
      )
    GROUP BY b.user_id
) next_free_ad ON a.id = next_free_ad.next_free_ad_id
        ', $rsm)
            ->execute();

        /** @var Eventadvert $advert */
        foreach ($adverts as $advert) {
            $user = $this->entityManager->getRepository(User::class)->find($advert->getUserId());
            if ($this->helperService->canPostFreeAdvert($user)) {
                $advert->setStatus(1);

                $this->entityManager->persist($advert);

                $io->info(sprintf('Activated %s', $advert->getId()));
            }
        }

        $this->entityManager->flush();

        return Command::SUCCESS;
    }
}
