<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr\Join;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use function get_class;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function getActivesUser()
    {
        $query = $this->createQueryBuilder('u');
        $query->where('u.enabled = :enabled');
        $query->andWhere('u.deleted = :deleted');
        $query->andWhere('u.blocked = :blocked');
        $query->setParameter('enabled', true);
        $query->setParameter('deleted', false);
        $query->setParameter('blocked', false);

        return $query->getQuery()->getResult();
    }

    /**
     * Search a user in admin panel
     */
    public function getUserByCriteria($id, $mail, $phone)
    {

        if ( $id == null && $mail == null && $phone == null ) return [];

        $query = $this->createQueryBuilder('u');
        $query->select('u.id', 'u.email', 'u.firstname', 'u.surname', 'u.deleted');

        if ( $id != null ) {
            $query->where('u.id = :id');
            $query->setParameter('id', $id);

            if ( $phone != null ) {
                $query->join(
                    'App\Entity\Company',
                    'c',
                    Join::WITH,
                    'u.id = c.userId'
                )
                ->andWhere('c.phonenumber = :phonenumber');
                $query->setParameter('phonenumber', $phone);
            }

            if ( $mail != null ) {
                $query->andWhere('u.email = :email');
                $query->setParameter('email', $mail);
            }
        } else {

            if ( $mail != null ) {
                $query->where('u.email = :email');
                $query->setParameter('email', $mail);

                if ( $phone != null ) {
                    $query->join(
                        'App\Entity\Company',
                        'c',
                        Join::WITH,
                        'u.id = c.userId'
                    )
                    ->andWhere('c.phonenumber = :phonenumber');
                    $query->setParameter('phonenumber', $phone);
                }
            }

            if ( $mail == null && $phone != null ) {
                $query->join(
                        'App\Entity\Company',
                        'c',
                        Join::WITH,
                        'u.id = c.userId'
                    )
                    ->where('c.phonenumber = :phonenumber');
               $query->setParameter('phonenumber', $phone);
            }

        }

        return $query->getQuery()->getOneOrNullResult();
    }

    // /**
    //  * @return User[] Returns an array of User objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('u.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
