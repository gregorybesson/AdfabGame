<?php

namespace AdfabGame\Mapper;

use Doctrine\ORM\EntityManager;
use AdfabGame\Options\ModuleOptions;

class TreasureHuntStep
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * @var \Doctrine\ORM\EntityRepository
     */
    protected $er;

    /**
     * @var \AdfabGame\Options\ModuleOptions
     */
    protected $options;

    public function __construct(EntityManager $em, ModuleOptions $options)
    {
        $this->em      = $em;
        $this->options = $options;
    }

    public function findById($id)
    {
        return $this->getEntityRepository()->find($id);
    }

    public function findByGameId($treasurehunt, $sortArray = array())
    {
        return $this->getEntityRepository()->findBy(array('treasurehunt' => $treasurehunt), $sortArray);
    }

    public function findBy($array = array(), $sortArray = array())
    {
        return $this->getEntityRepository()->findBy($array, $sortArray);
    }

    public function insert($entity)
    {
        return $this->persist($entity);
    }

    public function update($entity)
    {
        return $this->persist($entity);
    }

    protected function persist($entity)
    {
        $this->em->persist($entity);
        $this->em->flush();

        return $entity;
    }

    public function findAll()
    {
        return $this->getEntityRepository()->findAll();
    }

    public function remove($entity)
    {
        $this->em->remove($entity);
        $this->em->flush();
    }

    public function getEntityRepository()
    {
        if (null === $this->er) {
            $this->er = $this->em->getRepository('AdfabGame\Entity\TreasureHuntStep');
        }

        return $this->er;
    }
}
