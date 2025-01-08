<?php

namespace App\Repository;

use App\Entity\Tag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;
use Gedmo\Translatable\TranslatableListener;

/**
 * @method Tag|null find($id, $lockMode = null, $lockVersion = null)
 * @method Tag|null findOneBy(array $criteria, array $orderBy = null)
 * @method Tag[]    findAll()
 * @method Tag[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tag::class);
    }

    public function findByTag($tag, $locale)
    {
        $q = $this->createQueryBuilder('c')
            ->andWhere('c.name LIKE :tag')
            ->setParameter('tag', '%' . $tag . '%')
            ->orderBy('c.name', 'ASC')
            ->setMaxResults(20)
            ->getQuery();

        $q->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Gedmo\Translatable\Query\TreeWalker\TranslationWalker');
        $q->setHint(TranslatableListener::HINT_TRANSLATABLE_LOCALE, $locale);

        return $q->getResult();
    }
}
