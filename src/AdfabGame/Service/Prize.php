<?php

namespace AdfabGame\Service;

use Zend\Form\Form;
use Zend\ServiceManager\ServiceManagerAwareInterface;
use Zend\ServiceManager\ServiceManager;
use ZfcBase\EventManager\EventProvider;
use AdfabGame\Options\ModuleOptions;
use AdfabGame\Mapper\Prize as PrizeMapper;
use Zend\Stdlib\ErrorHandler;

class Prize extends EventProvider implements ServiceManagerAwareInterface
{

    /**
     * @var prizeMapper
     */
    protected $prizeMapper;

    /**
     * @var ServiceManager
     */
    protected $serviceManager;

    /**
     * @var UserServiceOptionsInterface
     */
    protected $options;

    /**
     *
     * This service is ready for all types of games
     *
     * @param  array                  $data
     * @param  string                 $entityClass
     * @param  string                 $formClass
     * @return \AdfabGame\Entity\Game
     */
    public function create(array $data, $prize, $formClass)
    {

        $form  = $this->getServiceManager()->get($formClass);
        $form->bind($prize);

        $form->setData($data);

        if (!$form->isValid()) {
            return false;
        }

        $prize = $this->getPrizeMapper()->insert($prize);

        return $prize;
    }

    /**
     *
     * @param  array                  $data
     * @param  string                 $entityClass
     * @param  string                 $formClass
     * @return \AdfabGame\Entity\Game
     */
    public function edit(array $data, $prize, $formClass)
    {

        $form  = $this->getServiceManager()->get($formClass);
        $form->bind($prize);

        $form->setData($data);

        if (!$form->isValid()) {
            return false;
        }

        $prize = $this->getPrizeMapper()->update($prize);

        return $prize;
    }


    /**
     * getPrizeMapper
     *
     * @return PrizeMapper
     */
    public function getPrizeMapper()
    {
        if (null === $this->prizeMapper) {
            $this->prizeMapper = $this->getServiceManager()->get('adfabgame_prize_mapper');
        }

        return $this->prizeMapper;
    }

    /**
     * setPrizeMapper
     *
     * @param  PrizeMapper $prizeMapper
     * @return User
     */
    public function setPrizeMapper(PrizeMapper $prizeMapper)
    {
        $this->prizeMapper = $prizeMapper;

        return $this;
    }

    public function setOptions(ModuleOptions $options)
    {
        $this->options = $options;

        return $this;
    }

    public function getOptions()
    {
        if (!$this->options instanceof ModuleOptions) {
            $this->setOptions($this->getServiceManager()->get('adfabgame_module_options'));
        }

        return $this->options;
    }

    /**
     * Retrieve service manager instance
     *
     * @return ServiceManager
     */
    public function getServiceManager()
    {
        return $this->serviceManager;
    }

    /**
     * Set service manager instance
     *
     * @param  ServiceManager $serviceManager
     * @return User
     */
    public function setServiceManager(ServiceManager $serviceManager)
    {
        $this->serviceManager = $serviceManager;

        return $this;
    }
}
