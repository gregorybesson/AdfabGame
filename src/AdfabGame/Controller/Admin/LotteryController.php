<?php

namespace AdfabGame\Controller\Admin;

use AdfabGame\Entity\Game;

use AdfabGame\Entity\Lottery;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class LotteryController extends AbstractActionController
{
    /**
     * @var GameService
     */
    protected $adminGameService;

    public function createLotteryAction()
    {
        $service = $this->getAdminGameService();
        $viewModel = new ViewModel();
        $viewModel->setTemplate('adfab-game/admin/lottery');

        $gameForm = new ViewModel();
        $gameForm->setTemplate('adfab-game/admin/game-form');

        $lottery = new Lottery();

        $form = $this->getServiceLocator()->get('adfabgame_lottery_form');
        $form->bind($lottery);
        $form->get('submit')->setAttribute('label', 'Add');
        $form->setAttribute('action', $this->url()->fromRoute('zfcadmin/adfabgame/create-lottery', array('gameId' => 0)));
        $form->setAttribute('method', 'post');

        $request = $this->getRequest();
        if ($request->isPost()) {
            $data = array_merge(
                $this->getRequest()->getPost()->toArray(),
                $this->getRequest()->getFiles()->toArray()
            );
            if (isset($data['drawDate']) && $data['drawDate']) {
                $data['drawDate'] = \DateTime::createFromFormat('d/m/Y', $data['drawDate']);
            }
               $game = $service->create($data, $lottery, 'adfabgame_lottery_form');
            if ($game) {
                $this->flashMessenger()->setNamespace('adfabgame')->addMessage('The game was created');

                return $this->redirect()->toRoute('zfcadmin/adfabgame/list');
            }
        }
        $gameForm->setVariables(array('form' => $form));
        $viewModel->addChild($gameForm, 'game_form');

        return $viewModel->setVariables(array('form' => $form, 'title' => 'Create lottery'));
    }

    public function editLotteryAction()
    {
        $service = $this->getAdminGameService();
        $gameId = $this->getEvent()->getRouteMatch()->getParam('gameId');

        if (!$gameId) {
            return $this->redirect()->toRoute('zfcadmin/adfabgame/createLottery');
        }

        $game = $service->getGameMapper()->findById($gameId);
        $viewModel = new ViewModel();
        $viewModel->setTemplate('adfab-game/admin/lottery/lottery');

        $gameForm = new ViewModel();
        $gameForm->setTemplate('adfab-game/admin/game-form');

        $form   = $this->getServiceLocator()->get('adfabgame_lottery_form');
        $form->setAttribute('action', $this->url()->fromRoute('zfcadmin/adfabgame/edit-lottery', array('gameId' => $gameId)));
        $form->setAttribute('method', 'post');
        $form->get('submit')->setLabel('Edit');
        if ($game->getFbAppId()) {
            $appIds = $form->get('fbAppId')->getOption('value_options');
            $appIds[$game->getFbAppId()] = $game->getFbAppId();
            $form->get('fbAppId')->setAttribute('options', $appIds);
        }

        $gameOptions = $this->getAdminGameService()->getOptions();
        $gameStylesheet = $gameOptions->getMediaPath() . '/' . 'stylesheet_'. $game->getId(). '.css';
        if (is_file($gameStylesheet)) {
            $values = $form->get('stylesheet')->getValueOptions();
            $values[$gameStylesheet] = 'Style personnalisé de ce jeu';

            $form->get('stylesheet')->setAttribute('options', $values);
        }

        $form->bind($game);

        if ($this->getRequest()->isPost()) {
            $data = array_merge(
                $this->getRequest()->getPost()->toArray(),
                $this->getRequest()->getFiles()->toArray()
            );
            if (isset($data['drawDate']) && $data['drawDate']) {
                $data['drawDate'] = \DateTime::createFromFormat('d/m/Y', $data['drawDate']);
            }
            $result = $service->edit($data, $game, 'adfabgame_lottery_form');

            if ($result) {
                return $this->redirect()->toRoute('zfcadmin/adfabgame/list');
            }
        }

        $gameForm->setVariables(array('form' => $form));
        $viewModel->addChild($gameForm, 'game_form');

        return $viewModel->setVariables(array('form' => $form, 'title' => 'Edit lottery'));
    }

    public function leaderboardAction()
    {
        $gameId         = $this->getEvent()->getRouteMatch()->getParam('gameId');
        $game           = $this->getAdminGameService()->getGameMapper()->findById($gameId);

        $entries = $this->getAdminGameService()->getEntryMapper()->findBy(array('game' => $game));

        if (is_array($entries)) {
            $paginator = new \Zend\Paginator\Paginator(new \Zend\Paginator\Adapter\ArrayAdapter($entries));
            $paginator->setItemCountPerPage(10);
            $paginator->setCurrentPageNumber($this->getEvent()->getRouteMatch()->getParam('p'));
        } else {
            $paginator = $entries;
        }

        return array(
                'entries' => $paginator,
                'game' => $game,
                'gameId' => $gameId
        );
    }

    public function downloadAction()
    {
        // magically create $content as a string containing CSV data
        $gameId         = $this->getEvent()->getRouteMatch()->getParam('gameId');
        $game           = $this->getAdminGameService()->getGameMapper()->findById($gameId);
        //$service        = $this->getLeaderBoardService();
        //$leaderboards   = $service->getLeaderBoardMapper()->findBy(array('game' => $game));

        $entries = $this->getAdminGameService()->getEntryMapper()->findBy(array('game' => $game,'winner' => 1));

        $content        = "\xEF\xBB\xBF"; // UTF-8 BOM
        $content       .= "ID;Pseudo;Civilité;Nom;Prénom;E-mail;Optin Metro;Optin partenaire;Eligible TAS ?;Date - H;Adresse;CP;Ville;Téléphone;Mobile;Date d'inscription;Date de naissance;\n";
        foreach ($entries as $e) {
        	if($e->getUser()->getAddress2() != '') {
        		$adress2 = ' - ' . $e->getUser()->getAddress2();
			} else {
				$adress2 = '';
			}
			if($e->getUser()->getDob() != NULL) {
				$dob = $e->getUser()->getDob()->format('Y-m-d');
			} else {
				$dob = '';
			}
			
            $content   .= $e->getUser()->getId()
                . ";" . $e->getUser()->getUsername()
				. ";" . $e->getUser()->getTitle()
                . ";" . $e->getUser()->getLastname()
                . ";" . $e->getUser()->getFirstname()
                . ";" . $e->getUser()->getEmail()
            	. ";" . $e->getUser()->getOptin()
                . ";" . $e->getUser()->getOptinPartner()
                . ";" . $e->getWinner()
				. ";" . $e->getCreatedAt()->format('Y-m-d H:i:s')
				. ";" . $e->getUser()->getAddress() . $adress2
				. ";" . $e->getUser()->getPostalCode()
				. ";" . $e->getUser()->getCity()
				. ";" . $e->getUser()->getTelephone()
				. ";" . $e->getUser()->getMobile()
				. ";" . $e->getUser()->getCreatedAt()->format('Y-m-d')
				. ";" . $dob
                ."\n";
        }

        $response = $this->getResponse();
        $headers = $response->getHeaders();
        $headers->addHeaderLine('Content-Encoding: UTF-8');
        $headers->addHeaderLine('Content-Type', 'text/csv; charset=UTF-8');
        $headers->addHeaderLine('Content-Disposition', "attachment; filename=\"leaderboard.csv\"");
        $headers->addHeaderLine('Accept-Ranges', 'bytes');
        $headers->addHeaderLine('Content-Length', strlen($content));

        $response->setContent($content);

        return $response;
    }

    public function drawAction()
    {
        // magically create $content as a string containing CSV data
        $gameId         = $this->getEvent()->getRouteMatch()->getParam('gameId');
        $game           = $this->getAdminGameService()->getGameMapper()->findById($gameId);
        //$service        = $this->getLeaderBoardService();
        //$leaderboards   = $service->getLeaderBoardMapper()->findBy(array('game' => $game));

        $winners = $this->getAdminGameService()->getEntryMapper()->draw($game);
        $nbWinners = $game->getWinners();

        $content        = "\xEF\xBB\xBF"; // UTF-8 BOM
        $content       .= "ID;Pseudo;Nom;Prenom;E-mail;Etat\n";
        $i=1;
        foreach ($winners as $w) {
            if ($i<=$nbWinners) {
                $etat = 'gagnant';
            } else {
                $etat = 'remplacant';
            }
            $content   .= $w->getId()
            . ";" . $w->getUsername()
            . ";" . $w->getLastname()
            . ";" . $w->getFirstname()
            . ";" . $w->getEmail()
            . ";" . $etat
            ."\n";
            $i++;
        }

        $response = $this->getResponse();
        $headers = $response->getHeaders();
        $headers->addHeaderLine('Content-Encoding: UTF-8');
        $headers->addHeaderLine('Content-Type', 'text/csv; charset=UTF-8');
        $headers->addHeaderLine('Content-Disposition', "attachment; filename=\"gagnants.csv\"");
        $headers->addHeaderLine('Accept-Ranges', 'bytes');
        $headers->addHeaderLine('Content-Length', strlen($content));

        $response->setContent($content);

        return $response;
    }

    public function getAdminGameService()
    {
        if (!$this->adminGameService) {
            $this->adminGameService = $this->getServiceLocator()->get('adfabgame_lottery_service');
        }

        return $this->adminGameService;
    }

    public function setAdminGameService(AdminGameService $adminGameService)
    {
        $this->adminGameService = $adminGameService;

        return $this;
    }
}
