<?php

namespace AdfabGame\Controller\Admin;

use AdfabGame\Entity\Game;

use AdfabGame\Entity\PostVote;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class PostVoteController extends AbstractActionController
{

    /**
     * @var GameService
     */
    protected $adminGameService;

    public function formAction()
    {
        $service = $this->getAdminGameService();
        $gameId = $this->getEvent()->getRouteMatch()->getParam('gameId');
        if (!$gameId) {
            return $this->redirect()->toRoute('zfcadmin/adfabgame/list');
        }
        $game = $service->getGameMapper()->findById($gameId);
        $form = $service->getPostVoteFormMapper()->findByGame($game);

        // I use the wonderful Form Generator to create the Post & Vote form
        $formgen = $this->forward()->dispatch('AdfabCore\Controller\Formgen', array('action' => 'create'));

        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost()->toArray();
            $form = $service->createForm($data, $game, $form);
            if ($form) {
                $this->flashMessenger()->setNamespace('adfabgame')->addMessage('The form was created');
            }
        }
        $formTemplate='';
        if ($form) {
            $formTemplate = $form->getFormTemplate();
        }

        return array(
        	'form' => $form,
        	'formTemplate' => $formTemplate,
        	'gameId' => $gameId,
        	'game' => $game,
		);
    }

    public function createPostVoteAction()
    {
        $service = $this->getAdminGameService();
        $viewModel = new ViewModel();
        $viewModel->setTemplate('post-vote');

        $gameForm = new ViewModel();
        $gameForm->setTemplate('game-form');

        $postVote = new PostVote();

        $form = $this->getServiceLocator()->get('adfabgame_postvote_form');
        $form->bind($postVote);
        $form->get('submit')->setAttribute('label', 'Add');
        $form->setAttribute('action', $this->url()->fromRoute('zfcadmin/adfabgame/create-postvote', array('gameId' => 0)));
        $form->setAttribute('method', 'post');

        $request = $this->getRequest();
        if ($request->isPost()) {
            $data = array_merge(
                    $this->getRequest()->getPost()->toArray(),
                    $this->getRequest()->getFiles()->toArray()
            );
            $game = $service->create($data, $postVote, 'adfabgame_postvote_form');
            if ($game) {
                $this->flashMessenger()->setNamespace('adfabgame')->addMessage('The game was created');

                return $this->redirect()->toRoute('zfcadmin/adfabgame/list');
            }
        }
        $gameForm->setVariables(array('form' => $form));
        $viewModel->addChild($gameForm, 'game_form');

        return $viewModel->setVariables(array('form' => $form, 'title' => 'Create Post & Vote'));
    }

    public function editPostVoteAction()
    {
        $service = $this->getAdminGameService();
        $gameId = $this->getEvent()->getRouteMatch()->getParam('gameId');

        if (!$gameId) {
            return $this->redirect()->toRoute('zfcadmin/adfabgame/create-postvote');
        }

        $game = $service->getGameMapper()->findById($gameId);
        $viewModel = new ViewModel();
        $viewModel->setTemplate('post-vote');

        $gameForm = new ViewModel();
        $gameForm->setTemplate('game-form');

        $form   = $this->getServiceLocator()->get('adfabgame_postvote_form');
        $form->setAttribute('action', $this->url()->fromRoute('zfcadmin/adfabgame/edit-postvote', array('gameId' => $gameId)));
        $form->setAttribute('method', 'post');
        $form->get('submit')->setLabel('Mettre à jour');
		
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
            $result = $service->edit($data, $game, 'adfabgame_postvote_form');

            if ($result) {
                return $this->redirect()->toRoute('zfcadmin/adfabgame/list');
            }
        }

        $gameForm->setVariables(array('form' => $form));
        $viewModel->addChild($gameForm, 'game_form');

        return $viewModel->setVariables(array('form' => $form, 'title' => 'Edit Post & Vote'));
    }

    public function modListAction()
    {
        $service = $this->getAdminGameService();
        $posts = $service->getPostVotePostMapper()->findBy(array('status' => 1));

        if (is_array($posts)) {
            $paginator = new \Zend\Paginator\Paginator(new \Zend\Paginator\Adapter\ArrayAdapter($posts));
            $paginator->setItemCountPerPage(10);
            $paginator->setCurrentPageNumber($this->getEvent()->getRouteMatch()->getParam('p'));
        } else {
            $paginator = $posts;
        }

        return array('posts' => $paginator);
    }

    public function moderationEditAction()
    {
        $service = $this->getAdminGameService();
        $postId = $this->getEvent()->getRouteMatch()->getParam('postId');
        $status = $this->getEvent()->getRouteMatch()->getParam('status');

        if (!$postId) {
            return $this->redirect()->toUrl($this->url()->fromRoute('zfcadmin/postvote/leaderboard', array('gameId' => 0)));
        }
        $post = $service->getPostVotePostMapper()->findById($postId);

        if (! $post) {
            return $this->redirect()->toUrl($this->url()->fromRoute('zfcadmin/postvote/leaderboard', array('gameId' => 0)));
        }
        $game = $post->getPostvote();

        if ($status && $status=='validation') {
            $post->setStatus(2);
            $service->getPostVotePostMapper()->update($post);

            return $this->redirect()->toUrl($this->url()->fromRoute('zfcadmin/postvote/leaderboard', array('gameId' => $game->getId())));
        } elseif ($status && $status=='rejection') {
            $post->setStatus(9);
            $service->getPostVotePostMapper()->update($post);

            return $this->redirect()->toUrl($this->url()->fromRoute('zfcadmin/postvote/leaderboard', array('gameId' => $game->getId())));
        }

        return array('game' => $game, 'post' => $post);
    }

    public function leaderboardAction()
    {
        $gameId         = $this->getEvent()->getRouteMatch()->getParam('gameId');
        $game           = $this->getAdminGameService()->getGameMapper()->findById($gameId);

        //$entries = $this->getAdminGameService()->getEntryMapper()->findBy(array('game' => $game));
        $posts   = $this->getAdminGameService()->getPostVotePostMapper()->findBy(array('postvote' => $game));

        if (is_array($posts)) {
            $paginator = new \Zend\Paginator\Paginator(new \Zend\Paginator\Adapter\ArrayAdapter($posts));
            $paginator->setItemCountPerPage(10);
            $paginator->setCurrentPageNumber($this->getEvent()->getRouteMatch()->getParam('p'));
        } else {
            $paginator = $posts;
        }

        return array(
                'posts' => $paginator,
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
		$posts   = $this->getAdminGameService()->getPostVotePostMapper()->findBy(array('postvote' => $game));

        $content        = "\xEF\xBB\xBF"; // UTF-8 BOM
        $content       .= "ID;Pseudo;Civilité;Nom;Prénom;E-mail;Optin Metro;Optin partenaire;Nb de votes;Date - H;Adresse;CP;Ville;Téléphone;Mobile;Date d'inscription;Date de naissance;\n";
        foreach ($posts as $e) {
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
            . ";" . count($e->getVotes())
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

    public function getAdminGameService()
    {
        if (!$this->adminGameService) {
            $this->adminGameService = $this->getServiceLocator()->get('adfabgame_postvote_service');
        }

        return $this->adminGameService;
    }

    public function setAdminGameService(AdminGameService $adminGameService)
    {
        $this->adminGameService = $adminGameService;

        return $this;
    }

}
