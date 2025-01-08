<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query;
use Gedmo\Translatable\TranslatableListener;

/**
 * @method Category|null find($id, $lockMode = null, $lockVersion = null)
 * @method Category|null findOneBy(array $criteria, array $orderBy = null)
 * @method Category[]    findAll()
 * @method Category[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    public function getFeatured($locale)
    {
        $builder = $this->createQueryBuilder('c')->andWhere('c.featured = 1')->orderBy('c.id', 'ASC');

        $query = $builder->getQuery();
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker');
        $query->setHint(TranslatableListener::HINT_TRANSLATABLE_LOCALE, $locale);

        return $query->getResult();
    }

    public function getByTitleSlug($locale, $slug)
    {
        $builder = $this->createQueryBuilder('c')->andWhere('c.titleSlug = :slug')->setParameter('slug', $slug);

        $query = $builder->getQuery();
        $query->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, 'Gedmo\\Translatable\\Query\\TreeWalker\\TranslationWalker');
        $query->setHint(TranslatableListener::HINT_TRANSLATABLE_LOCALE, $locale);

        return $query->getResult();
    }


    public function getParents()
    {
        return $this->createQueryBuilder('c')->andWhere('c.parent IS NULL')->orderBy('c.id', 'ASC')->getQuery()->getResult();
    }


    public function getParentsChid(int $parentId)
    {
        return $this->createQueryBuilder('c')->andWhere("c.parent = $parentId")->orderBy('c.id', 'ASC')->getQuery()->getResult();
    }

    public function getAllOrderedByChannel()
    {
        return $this->createQueryBuilder('c')
            //->select('c.id', 'c.title','c.description', 'c.featured', 'p.title as parent_name')
            //->join('c.parent', 'p')
            ->orderBy('c.channel', 'ASC')->orderBy('c.id', 'ASC')->getQuery()->getResult();
    }

    // /**
    //  * @return Category[] Returns an array of Category objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Category
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
