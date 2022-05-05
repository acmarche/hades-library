<?php

namespace AcMarche\Pivot\Repository;

use AcMarche\Pivot\Entity\Filtre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Filtre|null find($id, $lockMode = null, $lockVersion = null)
 * @method Filtre|null findOneBy(array $criteria, array $orderBy = null)
 * @method Filtre[]    findAll()
 * @method Filtre[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FiltreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Filtre::class);
    }

    /**
     * @return Filtre[]
     */
    public function findRoots(): array
    {
        return $this->createQueryBuilder('filtre')
            ->andWhere('filtre.parent IS NULL')
            ->orderBy('filtre.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Filtre[]
     */
    public function findWithChildren(): array
    {
        $roots = $this->findRoots();
        $filtres = [];
        foreach ($roots as $root) {
            $root->children = $this->findByParent($root->id);
            $filtres[] = $root;
        }

        return $filtres;
    }

    /**
     * @param integer $id
     * @return Filtre[]
     */
    public function findByParent(int $id): array
    {
        return $this->createQueryBuilder('filtre')
            ->andWhere('filtre.parent = :id')
            ->setParameter('id', $id)
            ->orderBy('filtre.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param string $name
     * @return Filtre[]
     */
    public function findByName(string $name): array
    {
        return $this->createQueryBuilder('filtre')
            ->andWhere('filtre.nom LIKE :nom')
            ->setParameter('nom', '%'.$name.'%')
            ->orderBy('filtre.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param array $params
     * @return Filtre[]
     */
    public function findByReferencesOrUrns(array $filtresData): array
    {
        $filtres = [];
        foreach ($filtresData as $filtreReference) {
            if ((int)$filtreReference) {
                if ($filtre = $this->findByReference($filtreReference)) {
                    $filtres[] = $filtre;
                }
            } else {
                if ($filtre = $this->findByUrn($filtreReference)) {
                    $filtres[] = $filtre;
                }
            }
        }

        return $filtres;
    }

    /**
     * @param int $id
     * @return Filtre|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findByReference(int $id): ?Filtre
    {
        return $this->createQueryBuilder('filtre')
            ->andWhere('filtre.reference = :id')
            ->setParameter('id', $id)
            ->orderBy('filtre.nom', 'ASC')
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByUrn(?string $urn): ?Filtre
    {
        return $this->createQueryBuilder('filtre')
            ->andWhere('filtre.urn = :urn')
            ->setParameter('urn', $urn)
            ->orderBy('filtre.nom', 'ASC')
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function insert(object $object): void
    {
        $this->persist($object);
        $this->flush();
    }

    public function persist(object $object): void
    {
        $this->_em->persist($object);
    }

    public function flush(): void
    {
        $this->_em->flush();
    }

    public function remove(object $object): void
    {
        $this->_em->remove($object);
    }

}