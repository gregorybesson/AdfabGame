<?php

namespace AdfabGame\Controller\Admin;

use AdfabGame\Entity\Game;

use AdfabGame\Entity\Quiz;
use AdfabGame\Entity\QuizQuestion;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class QuizController extends AbstractActionController
{

    /**
     * @var GameService
     */
    protected $adminGameService;

    public function listQuestionAction()
    {
        $service = $this->getAdminGameService();
        $quizId = $this->getEvent()->getRouteMatch()->getParam('quizId');
        if (!$quizId) {
            return $this->redirect()->toRoute('zfcadmin/adfabgame/list');
        }
        $quiz = $service->getGameMapper()->findById($quizId);
        $questions = $service->getQuizQuestionMapper()->findByGameId($quizId);

        if (is_array($questions)) {
            $paginator = new \Zend\Paginator\Paginator(new \Zend\Paginator\Adapter\ArrayAdapter($questions));
        } else {
            $paginator = $questions;
        }

        $paginator->setItemCountPerPage(10);
        $paginator->setCurrentPageNumber($this->getEvent()->getRouteMatch()->getParam('p'));

        return array(
        	'questions' => $paginator,
        	'quiz_id' => $quizId,
        	'quiz' => $quiz,
		);
    }

    public function addQuestionAction()
    {
        $viewModel = new ViewModel();
        $viewModel->setTemplate('adfab-game/admin/quiz/question');
        $service = $this->getAdminGameService();
        $quizId = $this->getEvent()->getRouteMatch()->getParam('quizId');

        if (!$quizId) {
            return $this->redirect()->toRoute('zfcadmin/adfabgame/list');
        }

        $form = $this->getServiceLocator()->get('adfabgame_quizquestion_form');
        $form->get('submit')->setAttribute('label', 'Ajouter');
        $form->get('quiz_id')->setAttribute('value', $quizId);
        $form->setAttribute('action', $this->url()->fromRoute('zfcadmin/adfabgame/quiz-question-add', array('quizId' => $quizId)));
        $form->setAttribute('method', 'post');

        $question = new QuizQuestion();
        $form->bind($question);

        if ($this->getRequest()->isPost()) {
            $data = array_merge(
                $this->getRequest()->getPost()->toArray(),
                $this->getRequest()->getFiles()->toArray()
            );
               $question = $service->createQuestion($data);
               if ($question) {
                   // Redirect to list of games
                   $this->flashMessenger()->setNamespace('adfabgame')->addMessage('The question was created');

                   return $this->redirect()->toRoute('zfcadmin/adfabgame/quiz-question-list', array('quizId'=>$quizId));
               }
        }

        return $viewModel->setVariables(array('form' => $form, 'quiz_id' => $quizId, 'question_id' => 0));
    }

    public function editQuestionAction()
    {
        $service = $this->getAdminGameService();
        $viewModel = new ViewModel();
        $viewModel->setTemplate('adfab-game/admin/quiz/question');

        $questionId = $this->getEvent()->getRouteMatch()->getParam('questionId');
        if (!$questionId) {
            return $this->redirect()->toRoute('zfcadmin/adfabgame/list');
        }
        $question   = $service->getQuizQuestionMapper()->findById($questionId);
        $quizId     = $question->getQuiz()->getId();

        $form = $this->getServiceLocator()->get('adfabgame_quizquestion_form');
        $form->get('submit')->setAttribute('label', 'Mettre à jour');
        $form->get('quiz_id')->setAttribute('value', $quizId);
        $form->setAttribute('action', $this->url()->fromRoute('zfcadmin/adfabgame/quiz-question-edit', array('questionId' => $questionId)));
        $form->setAttribute('method', 'post');

        $form->bind($question);

        if ($this->getRequest()->isPost()) {
            $data = array_merge(
                $this->getRequest()->getPost()->toArray(),
                $this->getRequest()->getFiles()->toArray()
            );
            $question = $service->updateQuestion($data, $question);
            if ($question) {
                // Redirect to list of games
                $this->flashMessenger()->setNamespace('adfabgame')->addMessage('The question was created');

                return $this->redirect()->toRoute('zfcadmin/adfabgame/quiz-question-list', array('quizId'=>$quizId));
            }
        }

        return $viewModel->setVariables(array('form' => $form, 'quiz_id' => $quizId, 'question_id' => $questionId));
    }

    public function removeQuestionAction()
    {
        $service = $this->getAdminGameService();
        $questionId = $this->getEvent()->getRouteMatch()->getParam('questionId');
        if (!$questionId) {
            return $this->redirect()->toRoute('zfcadmin/adfabgame/list');
        }
        $question   = $service->getQuizQuestionMapper()->findById($questionId);
        $quizId     = $question->getQuiz()->getId();

        $service->getQuizQuestionMapper()->remove($question);
        $this->flashMessenger()->setNamespace('adfabgame')->addMessage('The question was created');

        return $this->redirect()->toRoute('zfcadmin/adfabgame/quiz-question-list', array('quizId'=>$quizId));
    }

    public function createQuizAction()
    {
        $service = $this->getAdminGameService();
        $viewModel = new ViewModel();
        $viewModel->setTemplate('adfab-game/admin/quiz/quiz');

        $gameForm = new ViewModel();
        $gameForm->setTemplate('adfab-game/admin/game-form');

        $quiz = new Quiz();

        $form = $this->getServiceLocator()->get('adfabgame_quiz_form');
        $form->bind($quiz);
        $form->get('submit')->setAttribute('label', 'Add');
        $form->setAttribute('action', $this->url()->fromRoute('zfcadmin/adfabgame/create-quiz', array('gameId' => 0)));
        $form->setAttribute('method', 'post');

        $request = $this->getRequest();
        if ($request->isPost()) {
            $data = array_merge(
                $this->getRequest()->getPost()->toArray(),
                $this->getRequest()->getFiles()->toArray()
            );
               $game = $service->create($data, $quiz, 'adfabgame_quiz_form');
            if ($game) {
                $this->flashMessenger()->setNamespace('adfabgame')->addMessage('The game was created');

                return $this->redirect()->toRoute('zfcadmin/adfabgame/list');
            }
        }
        $gameForm->setVariables(array('form' => $form));
        $viewModel->addChild($gameForm, 'game_form');

        return $viewModel->setVariables(array('form' => $form, 'title' => 'Create quiz'));
    }

    public function editQuizAction()
    {
        $service = $this->getAdminGameService();
        $gameId = $this->getEvent()->getRouteMatch()->getParam('gameId');

        if (!$gameId) {
            return $this->redirect()->toRoute('zfcadmin/adfabgame/create-quiz');
        }

        $game = $service->getGameMapper()->findById($gameId);
        $viewModel = new ViewModel();
        $viewModel->setTemplate('adfab-game/admin/quiz/quiz');

        $gameForm = new ViewModel();
        $gameForm->setTemplate('adfab-game/admin/game-form');

        $form   = $this->getServiceLocator()->get('adfabgame_quiz_form');
        $form->setAttribute('action', $this->url()->fromRoute('zfcadmin/adfabgame/edit-quiz', array('gameId' => $gameId)));
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
            $result = $service->edit($data, $game, 'adfabgame_quiz_form');

            if ($result) {
                return $this->redirect()->toRoute('zfcadmin/adfabgame/list');
            }
        }

        $gameForm->setVariables(array('form' => $form));
        $viewModel->addChild($gameForm, 'game_form');

        return $viewModel->setVariables(array('form' => $form, 'title' => 'Edit quiz'));
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
        $sg             = $this->getAdminGameService();
        $game           = $this->getAdminGameService()->getGameMapper()->findById($gameId);

        $questions = $game->getQuestions();

        $label = "";
        $questionArray = array();
        $i = 0;
        foreach ($questions as $q) {
            if ($q->getType() == 0 || $q->getType() == 1) {
                foreach ($q->getAnswers() as $a) {
                    $questionArray[$i]['q'] = $q->getId();
                    $questionArray[$i]['a'] = $a->getId();
                    $questionArray[$i]['open'] = false;
                    $label .= ";" . strip_tags(str_replace("\r\n","",$q->getQuestion())) . " - " .strip_tags(str_replace("\r\n","",$a->getAnswer()));
                    $i++;
                }
            } elseif ($q->getType() == 2) {
                $questionArray[$i]['q'] = $q->getId();
                $questionArray[$i]['open'] = true;
                $questionArray[$i]['a'] = '';
                $label .= ";" . strip_tags(str_replace("\r\n","",$q->getQuestion()));
                $i++;
            }
        }

        $label =  html_entity_decode($label, ENT_QUOTES, 'UTF-8');

        $entries = $this->getAdminGameService()->getEntryMapper()->findBy(array('game' => $game));

        $content        = "\xEF\xBB\xBF"; // UTF-8 BOM
        $content       .= "ID;Pseudo;Civilité;Nom;Prénom;E-mail;Optin Metro;Optin partenaire;Eligible TAS ?" . $label . ";Date - H;Adresse;CP;Ville;Téléphone;Mobile;Date d'inscription;Date de naissance;\n";
        foreach ($entries as $e) {

            $replies   = $sg->getQuizReplyMapper()->getLastGameReply($e);
            $replyText = "";
            foreach ($questionArray as $q) {
                $found = false;
                if ($q['open'] == false) {
                    foreach ($replies as $reply) {
                       if ($q['q'] == $reply->getQuestionId() && $q['a'] == $reply->getAnswerId()) {
                           $replyText .= ";1";
                           $found = true;
                           break;
                       }
                    }
                    if (!$found) {
                        $replyText .= ";0";
                    }
                } else {
                    foreach ($replies as $reply) {
                        if ($q['q'] == $reply->getQuestionId()) {
                            $replyText .= ";" . strip_tags(str_replace("\r\n","",$reply->getAnswer()));
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        $replyText .= ";0";
                    }
                }
            }
			
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
            . $replyText
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
            $this->adminGameService = $this->getServiceLocator()->get('adfabgame_quiz_service');
        }

        return $this->adminGameService;
    }

    public function setAdminGameService(AdminGameService $adminGameService)
    {
        $this->adminGameService = $adminGameService;

        return $this;
    }
}
