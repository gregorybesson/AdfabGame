<?php

namespace AdfabGame\Service;

use Zend\ServiceManager\ServiceManagerAwareInterface;
use Zend\ServiceManager\ServiceManager;
use AdfabGame\Mapper\GameInterface as GameMapperInterface;

class Lottery extends Game implements ServiceManagerAwareInterface
{

    /**
     * @var LotteryMapperInterface
     */
    protected $lotteryMapper;

    public function getGameEntity()
    {
        return new \AdfabGame\Entity\Lottery;
    }

    /**
     * getLotteryMapper
     *
     * @return LotteryMapperInterface
     */
    public function getLotteryMapper()
    {
        if (null === $this->lotteryMapper) {
            $this->lotteryMapper = $this->getServiceManager()->get('adfabgame_lottery_mapper');
        }

        return $this->lotteryMapper;
    }

    /**
     * setLotteryMapper
     *
     * @param  LotteryMapperInterface $lotteryMapper
     * @return User
     */
    public function setLotteryMapper(GameMapperInterface $lotteryMapper)
    {
        $this->lotteryMapper = $lotteryMapper;

        return $this;
    }
}
