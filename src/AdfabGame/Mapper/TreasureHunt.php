<?php

namespace AdfabGame\Mapper;

use Doctrine\ORM\EntityManager;
use AdfabGame\Options\ModuleOptions;

class TreasureHunt extends Game
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * @var \AdfabGame\Options\ModuleOptions
     */
    protected $options;

    public function __construct(EntityManager $em, ModuleOptions $options)
    {
        parent::__construct($em, $options);
    }

    public function getEntityRepository()
    {
        if (null === $this->er) {
            $this->er = $this->em->getRepository('AdfabGame\Entity\TreasureHunt');
        }

        return $this->er;
    }
}
