<?php

namespace AdfabGame\Mapper;

use Doctrine\ORM\EntityManager;
use AdfabGame\Options\ModuleOptions;

class Quiz extends Game
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
        parent::__construct($em, $options);
    }

    public function getEntityRepository()
    {
        if (null === $this->er) {
            $this->er = $this->em->getRepository('AdfabGame\Entity\Quiz');
        }

        return $this->er;
    }
}
