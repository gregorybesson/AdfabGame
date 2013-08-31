<?php

namespace AdfabGame\Controller\Frontend;

use AdfabGame\Entity\Lottery;
use Zend\View\Model\ViewModel;

class LotteryController extends GameController
{

    /**
     * @var gameService
     */
    protected $gameService;

    public function playAction()
    {
        $identifier = $this->getEvent()->getRouteMatch()->getParam('id');
        $user = $this->zfcUserAuthentication()->getIdentity();
        $sg = $this->getGameService();

        $game = $sg->checkGame($identifier);
        if (!$game || $game->isClosed()) {
            return $this->notFoundAction();
        }

        if (!$user) {
            // the user is not registered yet.
            $redirect = urlencode($this->url()->fromRoute('frontend/lottery/play', array('id' => $game->getIdentifier()), array('force_canonical' => true)));

            return $this->redirect()->toUrl($this->url()->fromRoute('frontend/zfcuser/register') . '?redirect='.$redirect);
        }

        $entry = $sg->play($game, $user);
        if (!$entry) {
            // the user has already taken part of this game and the participation limit has been reache
            $this->flashMessenger()->addMessage('Vous avez déjà participé');

            return $this->redirect()->toUrl($this->url()->fromRoute('frontend/lottery/result',array('id' => $identifier)));
        }

        // Every participation is a winning one !
        $entry->setWinner(true);
        $entry->setActive(false);
        $sg->getEntryMapper()->update($entry);

        return $this->redirect()->toUrl($this->url()->fromRoute('frontend/lottery/result', array('id' => $identifier)));
    }

    public function resultAction()
    {
    	$identifier = $this->getEvent()->getRouteMatch()->getParam('id');
        $user = $this->zfcUserAuthentication()->getIdentity();
        $sg = $this->getGameService();

        $statusMail = null;

        $game = $sg->checkGame($identifier);
        if (!$game || $game->isClosed()) {
            return $this->notFoundAction();
        }

        $secretKey = strtoupper(substr(sha1($user->getId().'####'.time()),0,15));
        $socialLinkUrl = $this->url()->fromRoute('frontend/lottery', array('id' => $game->getIdentifier()), array('force_canonical' => true)).'?key='.$secretKey;
        // With core shortener helper
        $socialLinkUrl = $this->shortenUrl()->shortenUrl($socialLinkUrl);

        $lastEntry = $sg->getEntryMapper()->findLastInactiveEntryById($game, $user);
        if (!$lastEntry) {
            return $this->redirect()->toUrl($this->url()->fromRoute('frontend/lottery', array('id' => $game->getIdentifier()), array('force_canonical' => true)));
        }

        $form = $this->getServiceLocator()->get('adfabgame_sharemail_form');
        $form->setAttribute('method', 'post');

        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost()->toArray();
            $form->setData($data);
            if ($form->isValid()) {
                $result = $this->getGameService()->sendShareMail($data, $game, $user);
                if ($result) {
                    $statusMail = true;
                    $bonusEntry = $sg->playBonus($game, $user, 1);
                }
            }
        }

        $viewModel = $this->buildView($game);
        $viewModel->setVariables(array(
                'statusMail'       => $statusMail,
                'game'             => $game,
                'flashMessages'    => $this->flashMessenger()->getMessages(),
                'form'             => $form,
                'socialLinkUrl'    => $socialLinkUrl,
                'secretKey'		   => $secretKey
            )
        );

        return $viewModel;
    }

    public function fbshareAction()
    {
         $sg = $this->getGameService();
         $result = parent::fbshareAction();
         $bonusEntry = false;

         if ($result) {
             $identifier = $this->getEvent()->getRouteMatch()->getParam('id');
             $user = $this->zfcUserAuthentication()->getIdentity();
             $game = $sg->checkGame($identifier);
             $bonusEntry = $sg->playBonus($game, $user, 1);
         }

         $response = $this->getResponse();
         $response->setContent(\Zend\Json\Json::encode(array(
            'success' => $result,
            'playBonus' => $bonusEntry
         )));

         return $response;
    }

    public function fbrequestAction()
    {
        $sg = $this->getGameService();
        $result = parent::fbrequestAction();
        $bonusEntry = false;

        if ($result) {
            $identifier = $this->getEvent()->getRouteMatch()->getParam('id');
            $user = $this->zfcUserAuthentication()->getIdentity();
            $game = $sg->checkGame($identifier);
            $bonusEntry = $sg->playBonus($game, $user, 1);
        }

        $response = $this->getResponse();
        $response->setContent(\Zend\Json\Json::encode(array(
            'success' => $result,
            'playBonus' => $bonusEntry
        )));

        return $response;
    }

    public function tweetAction()
    {
        $sg = $this->getGameService();
        $result = parent::tweetAction();
        $bonusEntry = false;

        if ($result) {
            $identifier = $this->getEvent()->getRouteMatch()->getParam('id');
            $user = $this->zfcUserAuthentication()->getIdentity();
            $game = $sg->checkGame($identifier);
            $bonusEntry = $sg->playBonus($game, $user, 1);
        }

        $response = $this->getResponse();
        $response->setContent(\Zend\Json\Json::encode(array(
            'success' => $result,
            'playBonus' => $bonusEntry
        )));

        return $response;
    }

    public function googleAction()
    {
        $sg = $this->getGameService();
        $result = parent::googleAction();
        $bonusEntry = false;

        if ($result) {
            $identifier = $this->getEvent()->getRouteMatch()->getParam('id');
            $user = $this->zfcUserAuthentication()->getIdentity();
            $game = $sg->checkGame($identifier);
            $bonusEntry = $sg->playBonus($game, $user, 1);
        }

        $response = $this->getResponse();
        $response->setContent(\Zend\Json\Json::encode(array(
            'success' => $result,
            'playBonus' => $bonusEntry
        )));

        return $response;
    }

    public function getGameService()
    {
        if (!$this->gameService) {
            $this->gameService = $this->getServiceLocator()->get('adfabgame_lottery_service');
        }

        return $this->gameService;
    }

    public function setGameService(GameService $gameService)
    {
        $this->gameService = $gameService;

        return $this;
    }
}
