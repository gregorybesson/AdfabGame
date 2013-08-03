<?php
/**
 * dependency Core
 * @author gbesson
 *
 */
namespace AdfabGame;

use Zend\Session\Container;
use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use Zend\Validator\AbstractValidator;

class Module
{
    public function onBootstrap(MvcEvent $e)
    {
        $serviceManager = $e->getApplication()->getServiceManager();

        /* Set the translator for default validation messages
         * I've copy/paste the Validator messages from ZF2 and placed them in a correct path : AdfabCore
         * TODO : Centraliser la trad pour les Helper et les Plugins
         */
        $translator = $serviceManager->get('translator');
        //$translator->setLocale(\Locale::acceptFromHttp($_SERVER['HTTP_ACCEPT_LANGUAGE']));

        AbstractValidator::setDefaultTranslator($translator,'adfabcore');

        $eventManager        = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);

        // If AdfabCms is installed, I can add my own dynareas to benefit from this feature
        $e->getApplication()->getEventManager()->getSharedManager()->attach('Zend\Mvc\Application','getDynareas', array($this, 'updateDynareas'));

        // I can post cron tasks to be scheduled by the core cron service
        $e->getApplication()->getEventManager()->getSharedManager()->attach('Zend\Mvc\Application','getCronjobs', array($this, 'addCronjob'));

        // If cron is called, the $e->getRequest()->getPost() produces an error so I protect it with
        // this test
        if ((get_class($e->getRequest()) == 'Zend\Console\Request')) {
            return;
        }

        // the Facebook Container is created and updated through AdfabCore which detects a call from Facebook
        $eventManager->attach("dispatch", function($e) {
        	$session = new Container('facebook');
        	if ($session->offsetExists('signed_request')){
        		$viewModel = $e->getViewModel()->setTemplate('layout/facebook');
        		$viewModel->facebooktemplate = true;
        	}
        });

    }

    /**
     * This method get the games and add them as Dynareas to AdfabCms so that blocks can be dynamically added to the games.
     *
     * @param  EventManager $e
     * @return array
     */
    public function updateDynareas($e)
    {
        $dynareas = $e->getParam('dynareas');
        //$dynareas = array_merge($dynareas, array('column_left' => array('title' => 'yeah', 'description' => 'bum rush it', 'location'=>'et hop')));

        $gameService = $e->getTarget()->getServiceManager()->get('adfabgame_game_service');

        $games = $gameService->getActiveGames();

        foreach ($games as $game) {
           $array = array('game'.$game->getId() => array('title' => $game->getTitle(), 'description' => $game->getClassType(), 'location'=>'pages du jeu'));
           $dynareas = array_merge($dynareas, $array);
        }

        return $dynareas;
    }

    /**
     * This method get the cron config for this module an add them to the listener
     * TODO : déporter la def des cron dans la config.
     *
     * @param  EventManager $e
     * @return array
     */
    public function addCronjob($e)
    {
        $cronjobs = $e->getParam('cronjobs');

        $cronjobs['adfagame_email'] = array(
            'frequency' => '*/15 * * * *',
            'callback'  => '\AdfabGame\Service\Game::cronMail',
            'args'      => array('bar', 'baz'),
        );

        // tous les jours à 5:00 AM
        $cronjobs['adfagame_instantwin_email'] = array(
                'frequency' => '* 5 * * *',
                'callback'  => '\AdfabGame\Service\Cron::instantWinEmail',
                'args'      => array(),
        );

        return $cronjobs;
    }

    public function getConfig()
    {
        return include __DIR__ . '/../../config/module.config.php';
    }

    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/../../src/' . __NAMESPACE__,
                ),
            ),
        );
    }

    /**
     * @return array
     */
    public function getViewHelperConfig()
    {
        return array(
            'factories' => array(
                'adfabPrizeCategory' => function($sm) {
                    $locator = $sm->getServiceLocator();
                    $viewHelper = new View\Helper\PrizeCategory;
                    $viewHelper->setPrizeCategoryService($locator->get('adfabgame_prizecategory_service'));

                    return $viewHelper;
                },
            ),
        );
    }

    public function getServiceConfig()
    {
        return array(
            'aliases' => array(
                // An alias for linking a partner service with AdfabGame without adherence
                'adfabgame_partner_service' => 'adfabpartnership_partner_service',
                'adfabgame_message'         => 'adfabcore_message',

            ),

            'invokables' => array(
                'adfabgame_game_service'              => 'AdfabGame\Service\Game',
                'adfabgame_lottery_service'           => 'AdfabGame\Service\Lottery',
                'adfabgame_postvote_service'          => 'AdfabGame\Service\PostVote',
                'adfabgame_quiz_service'              => 'AdfabGame\Service\Quiz',
            	'adfabgame_treasurehunt_service'      => 'AdfabGame\Service\TreasureHunt',
                'adfabgame_instantwin_service'        => 'AdfabGame\Service\InstantWin',
                'adfabgame_leaderboard_service'       => 'AdfabGame\Service\LeaderBoard',
            	'adfabgame_prize_service'     		  => 'AdfabGame\Service\Prize',
            	'adfabgame_prizecategory_service'     => 'AdfabGame\Service\PrizeCategory',
                'adfabgame_prizecategoryuser_service' => 'AdfabGame\Service\PrizeCategoryUser',
            ),

            'factories' => array(
                'adfabgame_module_options' => function ($sm) {
                        $config = $sm->get('Configuration');

                        return new Options\ModuleOptions(isset($config['adfabgame']) ? $config['adfabgame'] : array()
                    );
                },

                'adfabgame_game_mapper' => function ($sm) {
                    $mapper = new \AdfabGame\Mapper\Game(
                            $sm->get('doctrine.entitymanager.orm_default'),
                            $sm->get('adfabgame_module_options')
                    );

                    return $mapper;
                },

                'adfabgame_lottery_mapper' => function ($sm) {
                    $mapper = new \AdfabGame\Mapper\Lottery(
                            $sm->get('doctrine.entitymanager.orm_default'),
                            $sm->get('adfabgame_module_options')
                    );

                    return $mapper;
                },

                'adfabgame_instantwin_mapper' => function ($sm) {
                    $mapper = new \AdfabGame\Mapper\InstantWin(
                        $sm->get('doctrine.entitymanager.orm_default'),
                        $sm->get('adfabgame_module_options')
                    );

                    return $mapper;
                },

                'adfabgame_instantwinoccurrence_mapper' => function ($sm) {
                    $mapper = new \AdfabGame\Mapper\InstantWinOccurrence(
                        $sm->get('doctrine.entitymanager.orm_default'),
                        $sm->get('adfabgame_module_options')
                    );

                    return $mapper;
                },

                'adfabgame_quiz_mapper' => function ($sm) {
                $mapper = new \AdfabGame\Mapper\Quiz(
                        $sm->get('doctrine.entitymanager.orm_default'),
                        $sm->get('adfabgame_module_options')
                );

                return $mapper;
                },

                'adfabgame_leaderboard_mapper' => function ($sm) {
                $mapper = new \AdfabGame\Mapper\LeaderBoard(
                        $sm->get('doctrine.entitymanager.orm_default'),
                        $sm->get('adfabgame_module_options')
                );

                return $mapper;
                },

                'adfabgame_quizquestion_mapper' => function ($sm) {
                    $mapper = new \AdfabGame\Mapper\QuizQuestion(
                            $sm->get('doctrine.entitymanager.orm_default'),
                            $sm->get('adfabgame_module_options')
                    );

                    return $mapper;
                },

                'adfabgame_quizanswer_mapper' => function ($sm) {
                $mapper = new \AdfabGame\Mapper\QuizAnswer(
                        $sm->get('doctrine.entitymanager.orm_default'),
                        $sm->get('adfabgame_module_options')
                );

                return $mapper;
                },

                'adfabgame_quizreply_mapper' => function ($sm) {
                    $mapper = new \AdfabGame\Mapper\QuizReply(
                        $sm->get('doctrine.entitymanager.orm_default'),
                        $sm->get('adfabgame_module_options')
                    );

                    return $mapper;
                },

                'adfabgame_entry_mapper' => function ($sm) {
                    $mapper = new \AdfabGame\Mapper\Entry(
                        $sm->get('doctrine.entitymanager.orm_default'),
                        $sm->get('adfabgame_module_options')
                    );

                    return $mapper;
                },

                'adfabgame_postvote_mapper' => function ($sm) {
                    $mapper = new \AdfabGame\Mapper\PostVote(
                        $sm->get('doctrine.entitymanager.orm_default'),
                        $sm->get('adfabgame_module_options')
                    );

                    return $mapper;
                },

                'adfabgame_postvoteform_mapper' => function ($sm) {
                $mapper = new \AdfabGame\Mapper\PostVoteForm(
                        $sm->get('doctrine.entitymanager.orm_default'),
                        $sm->get('adfabgame_module_options')
                );

                return $mapper;
                },

                'adfabgame_postvotepost_mapper' => function ($sm) {
                    $mapper = new \AdfabGame\Mapper\PostVotePost(
                        $sm->get('doctrine.entitymanager.orm_default'),
                        $sm->get('adfabgame_module_options')
                    );

                    return $mapper;
                },

                'adfabgame_postvotepostelement_mapper' => function ($sm) {
                    $mapper = new \AdfabGame\Mapper\PostVotePostElement(
                        $sm->get('doctrine.entitymanager.orm_default'),
                        $sm->get('adfabgame_module_options')
                    );

                    return $mapper;
                },

                'adfabgame_postvotevote_mapper' => function ($sm) {
                    $mapper = new \AdfabGame\Mapper\PostVoteVote(
                        $sm->get('doctrine.entitymanager.orm_default'),
                        $sm->get('adfabgame_module_options')
                    );

                    return $mapper;
                },

                'adfabgame_prize_mapper' => function ($sm) {
                	$mapper = new \AdfabGame\Mapper\Prize(
                		$sm->get('doctrine.entitymanager.orm_default'),
                		$sm->get('adfabgame_module_options')
                	);

                	return $mapper;
                },

                'adfabgame_prizecategory_mapper' => function ($sm) {
                    $mapper = new \AdfabGame\Mapper\PrizeCategory(
                        $sm->get('doctrine.entitymanager.orm_default'),
                        $sm->get('adfabgame_module_options')
                    );

                    return $mapper;
                },

                'adfabgame_prizecategoryuser_mapper' => function ($sm) {
                    $mapper = new \AdfabGame\Mapper\PrizeCategoryUser(
                        $sm->get('doctrine.entitymanager.orm_default'),
                        $sm->get('adfabgame_module_options')
                    );

                    return $mapper;
                },
                
                'adfabgame_treasurehuntstep_mapper' => function ($sm) {
                	$mapper = new \AdfabGame\Mapper\TreasureHuntStep(
                			$sm->get('doctrine.entitymanager.orm_default'),
                			$sm->get('adfabgame_module_options')
                	);
                
                	return $mapper;
                },

                'adfabgame_game_form' => function($sm) {
                    $translator = $sm->get('translator');
                    $form = new Form\Admin\Game(null, $sm, $translator);
                    $game = new Entity\Game();
                    $form->setInputFilter($game->getInputFilter());

                    return $form;
                },

                'adfabgame_lottery_form' => function($sm) {
                    $translator = $sm->get('translator');
                    $form = new Form\Admin\Lottery(null, $sm, $translator);
                    $lottery = new Entity\Lottery();
                    $form->setInputFilter($lottery->getInputFilter());

                    return $form;
                },

                'adfabgame_quiz_form' => function($sm) {
                    $translator = $sm->get('translator');
                    $form = new Form\Admin\Quiz(null, $sm, $translator);
                    $quiz = new Entity\Quiz();
                    $form->setInputFilter($quiz->getInputFilter());

                    return $form;
                },

                'adfabgame_instantwin_form' => function($sm) {
                    $translator = $sm->get('translator');
                    $form = new Form\Admin\InstantWin(null, $sm, $translator);
                    $instantwin = new Entity\InstantWin();
                    $form->setInputFilter($instantwin->getInputFilter());

                    return $form;
                },

                'adfabgame_quizquestion_form' => function($sm) {
                    $translator = $sm->get('translator');
                    $form = new Form\Admin\QuizQuestion(null, $sm, $translator);
                    $quizQuestion = new Entity\QuizQuestion();
                    $form->setInputFilter($quizQuestion->getInputFilter());

                    return $form;
                },

                'adfabgame_instantwinoccurrence_form' => function($sm) {
                    $translator = $sm->get('translator');
                    $form = new Form\Admin\InstantWinOccurrence(null, $sm, $translator);
                    $instantwinOccurrence = new Entity\InstantWinOccurrence();
                    $form->setInputFilter($instantwinOccurrence->getInputFilter());

                    return $form;
                },

                'adfabgame_postvote_form' => function($sm) {
                    $translator = $sm->get('translator');
                    $form = new Form\Admin\PostVote(null, $sm, $translator);
                    $postVote = new Entity\PostVote();
                    $form->setInputFilter($postVote->getInputFilter());

                    return $form;
                },

                'adfabgame_prizecategory_form' => function($sm) {
                    $translator = $sm->get('translator');
                    $form = new Form\Admin\PrizeCategory(null, $sm, $translator);
                    $prizeCategory = new Entity\PrizeCategory();
                    $form->setInputFilter($prizeCategory->getInputFilter());

                    return $form;
                },

                'adfabgame_prizecategoryuser_form' => function($sm) {
                $translator = $sm->get('translator');
                $form = new Form\Frontend\PrizeCategoryUser(null, $sm, $translator);

                return $form;
                },

                'adfabgame_sharemail_form' => function($sm) {
                    $translator = $sm->get('translator');
                    $form = new Form\Frontend\ShareMail(null, $sm, $translator);
                    $form->setInputFilter(new Form\Frontend\ShareMailFilter());

                    return $form;
                },
                
                'adfabgame_treasurehunt_form' => function($sm) {
                	$translator = $sm->get('translator');
                	$form = new Form\Admin\TreasureHunt(null, $sm, $translator);
                	$treasurehunt = new Entity\TreasureHunt();
                	$form->setInputFilter($treasurehunt->getInputFilter());
                
                	return $form;
                },
                
                'adfabgame_treasurehuntstep_form' => function($sm) {
                	$translator = $sm->get('translator');
                	$form = new Form\Admin\TreasureHuntStep(null, $sm, $translator);
                	$treasurehuntStep = new Entity\TreasureHuntStep();
                	$form->setInputFilter($treasurehuntStep->getInputFilter());
                
                	return $form;
                },
            ),
        );
    }
}
