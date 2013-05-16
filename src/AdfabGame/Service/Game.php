<?php

namespace AdfabGame\Service;

use AdfabGame\Entity\LeaderBoard;
use AdfabGame\Entity\Entry;

use Zend\Form\Form;
use Zend\ServiceManager\ServiceManagerAwareInterface;
use Zend\ServiceManager\ServiceManager;
use ZfcBase\EventManager\EventProvider;
use AdfabGame\Options\ModuleOptions;
use AdfabGame\Mapper\GameInterface as GameMapperInterface;
use DoctrineModule\Validator\NoObjectExists as NoObjectExistsValidator;
use Zend\File\Transfer\Adapter\Http;
use Zend\Validator\File\Size;
use Zend\Validator\File\IsImage;
use Zend\Stdlib\ErrorHandler;

class Game extends EventProvider implements ServiceManagerAwareInterface
{

    protected $leaderBoardService;

    /**
     * @var GameMapperInterface
     */
    protected $gameMapper;

    /**
     * @var EntryMapperInterface
     */
    protected $entryMapper;

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
    public function create(array $data, $entity, $formClass)
    {
        $game  = new $entity;
        $entityManager = $this->getServiceManager()->get('zfcuser_doctrine_em');

        $form  = $this->getServiceManager()->get($formClass);
        $form->bind($game);

        $path = $this->getOptions()->getMediaPath() . '/';
        $media_url = $this->getOptions()->getMediaUrl() . '/';

        $identifierInput = $form->getInputFilter()->get('identifier');
        $noObjectExistsValidator = new NoObjectExistsValidator(array(
            'object_repository' => $entityManager->getRepository('AdfabGame\Entity\Game'),
            'fields'            => 'identifier',
            'messages'          => array('objectFound' => 'This url already exists !')
        ));

        $identifierInput->getValidatorChain()->addValidator($noObjectExistsValidator);

        if (isset($data['publicationDate']) && $data['publicationDate']) {
            $data['publicationDate'] = \DateTime::createFromFormat('d/m/Y', $data['publicationDate']);
        }
        if (isset($data['startDate']) && $data['startDate']) {
            $data['startDate'] = \DateTime::createFromFormat('d/m/Y', $data['startDate']);
        }
        if (isset($data['endDate']) && $data['endDate']) {
            $data['endDate'] = \DateTime::createFromFormat('d/m/Y', $data['endDate']);
        }
        if (isset($data['closeDate']) && $data['closeDate']) {
            $data['closeDate'] = \DateTime::createFromFormat('d/m/Y', $data['closeDate']);
        }

        // If publicationDate is null, I update it with the startDate if not null neither
        if (!isset($data['publicationDate']) && isset($data['startDate'])) {
            $data['publicationDate'] = $data['startDate'];
        }

        // If the identifier has not been set, I use the title to create one.
        if (empty($data['identifier']) && !empty($data['title'])) {
            $data['identifier'] = $data['title'];
        }

        $form->setData($data);

        if (!$form->isValid()) {
            return false;
        }

        $game = $this->getGameMapper()->insert($game);

        // If I receive false, it means that the FB Id was not available anymore
        $result = $this->getEventManager()->trigger(__FUNCTION__, $this, array('game' => $game));
        if(!$result) return false;

        // I wait for the game to be saved to obtain its ID.
        if (!empty($data['uploadStylesheet']['tmp_name'])) {
            ErrorHandler::start();
            move_uploaded_file($data['uploadStylesheet']['tmp_name'], $path . 'stylesheet_'. $game->getId() .'.css');
            $game->setStylesheet($media_url . 'stylesheet_'. $game->getId() .'.css');
            ErrorHandler::stop(true);
        }

        if (!empty($data['uploadMainImage']['tmp_name'])) {
            ErrorHandler::start();
            move_uploaded_file($data['uploadMainImage']['tmp_name'], $path . $game->getId() . "-" . $data['uploadMainImage']['name']);
            $game->setMainImage($media_url . $game->getId() . "-" . $data['uploadMainImage']['name']);
            ErrorHandler::stop(true);
        }

        if (!empty($data['uploadSecondImage']['tmp_name'])) {
            ErrorHandler::start();
            move_uploaded_file($data['uploadSecondImage']['tmp_name'], $path . $game->getId() . "-" . $data['uploadSecondImage']['name']);
            $game->setSecondImage($media_url . $game->getId() . "-" . $data['uploadSecondImage']['name']);
            ErrorHandler::stop(true);
        }

        if (!empty($data['uploadFbShareImage']['tmp_name'])) {
            ErrorHandler::start();
            move_uploaded_file($data['uploadFbShareImage']['tmp_name'], $path . $game->getId() . "-" . $data['uploadFbShareImage']['name']);
            $game->setFbShareImage($media_url . $game->getId() . "-" . $data['uploadFbShareImage']['name']);
            ErrorHandler::stop(true);
        }

        if (!empty($data['uploadFbPageTabImage']['tmp_name'])) {
            ErrorHandler::start();
            $extension = $this->getExtension ( strtolower ( $data['uploadFbPageTabImage']['name'] ) );
            $src = $this->get_src ($extension, $data['uploadFbPageTabImage']['tmp_name']);
            $this->resize($data['uploadFbPageTabImage']['tmp_name'],$extension, $path . $game->getId() . "-" . $data['uploadFbPageTabImage']['name'], $src,  111, 74);

            //move_uploaded_file($data['uploadFbPageTabImage']['tmp_name'], $path . $game->getId() . "-" . $data['uploadFbPageTabImage']['name']);

            $game->setFbPageTabImage($media_url . $game->getId() . "-" . $data['uploadFbPageTabImage']['name']);
            ErrorHandler::stop(true);
        }
        $game = $this->getGameMapper()->update($game);

        return $game;
    }

    /**
     *
     * This service is ready for all types of games
     *
     * @param  array                  $data
     * @param  string                 $entityClass
     * @param  string                 $formClass
     * @return \AdfabGame\Entity\Game
     */
    public function edit(array $data, $game, $formClass)
    {
        $entityManager = $this->getServiceManager()->get('zfcuser_doctrine_em');
        $form  = $this->getServiceManager()->get($formClass);
        $form->bind($game);

        $path = $this->getOptions()->getMediaPath() . '/';
        $media_url = $this->getOptions()->getMediaUrl() . '/';
        if (isset($data['publicationDate']) && $data['publicationDate']) {
            $data['publicationDate'] = \DateTime::createFromFormat('d/m/Y', $data['publicationDate']);
        }
        if (isset($data['startDate']) && $data['startDate']) {
            $data['startDate'] = \DateTime::createFromFormat('d/m/Y', $data['startDate']);
        }
        if (isset($data['endDate']) && $data['endDate']) {
            $data['endDate'] = \DateTime::createFromFormat('d/m/Y', $data['endDate']);
        }
        if (isset($data['closeDate']) && $data['closeDate']) {
            $data['closeDate'] = \DateTime::createFromFormat('d/m/Y', $data['closeDate']);
        }

        // If publicationDate is null, I update it with the startDate if not nul neither
        if ((!isset($data['publicationDate']) || $data['publicationDate'] == '') && (isset($data['startDate']) && $data['startDate'] != '')) {
            $data['publicationDate'] = $data['startDate'];
        }

        if (!isset($data['identifier']) && isset($data['title'])) {
            $data['identifier'] = $data['title'];
        }

        $form->setData($data);

        // If someone want to claim... It's time to do it ! used for exemple by AdfabFacebook Module
        $result = $this->getEventManager()->trigger(__FUNCTION__.'.validate', $this, array('game' => $game, 'data' => $data));
        if (!$result[0]) {
            $form->get('fbAppId')->setMessages(array('Vous devez d\'abord désinstaller l\'appli Facebook'));

            return false;
        }

        if (!$form->isValid()) {
            return false;
        }

        if (!empty($data['uploadMainImage']['tmp_name'])) {
            ErrorHandler::start();
            move_uploaded_file($data['uploadMainImage']['tmp_name'], $path . $game->getId() . "-" . $data['uploadMainImage']['name']);
            $game->setMainImage($media_url . $game->getId() . "-" . $data['uploadMainImage']['name']);
            ErrorHandler::stop(true);
        }

        if (!empty($data['uploadSecondImage']['tmp_name'])) {
            ErrorHandler::start();
            move_uploaded_file($data['uploadSecondImage']['tmp_name'], $path . $game->getId() . "-" . $data['uploadSecondImage']['name']);
            $game->setSecondImage($media_url . $game->getId() . "-" . $data['uploadSecondImage']['name']);
            ErrorHandler::stop(true);
        }

        if (!empty($data['uploadStylesheet']['tmp_name'])) {
            ErrorHandler::start();
            move_uploaded_file($data['uploadStylesheet']['tmp_name'], $path . 'stylesheet_'. $game->getId() .'.css');
            $game->setStylesheet($media_url . 'stylesheet_'. $game->getId() .'.css');
            ErrorHandler::stop(true);
        }

        if (!empty($data['uploadFbShareImage']['tmp_name'])) {
            ErrorHandler::start();
            move_uploaded_file($data['uploadFbShareImage']['tmp_name'], $path . $game->getId() . "-" . $data['uploadFbShareImage']['name']);
            $game->setFbShareImage($media_url . $game->getId() . "-" . $data['uploadFbShareImage']['name']);
            ErrorHandler::stop(true);
        }

        if (!empty($data['uploadFbPageTabImage']['tmp_name'])) {
            ErrorHandler::start();

            $extension = $this->getExtension ( strtolower ( $data['uploadFbPageTabImage']['name'] ) );
            $src = $this->get_src ($extension, $data['uploadFbPageTabImage']['tmp_name']);
            $this->resize($data['uploadFbPageTabImage']['tmp_name'],$extension, $path . $game->getId() . "-" . $data['uploadFbPageTabImage']['name'], $src,  111, 74);
            //move_uploaded_file($data['uploadFbPageTabImage']['tmp_name'], $path . $game->getId() . "-" . $data['uploadFbPageTabImage']['name']);

            $game->setFbPageTabImage($media_url . $game->getId() . "-" . $data['uploadFbPageTabImage']['name']);
            ErrorHandler::stop(true);
        }

        /*if ($fileName) {
            $adapter = new \Zend\File\Transfer\Adapter\Http();
            $size = new Size(array('max'=>2000000));
            $adapter->setValidators(array($size), $fileName);

            if (!$adapter->isValid()) {
                $dataError = $adapter->getMessages();
                $error = array();
                foreach ($dataError as $key=>$row) {
                    $error[] = $row;
                }
                $form->setMessages(array('main_image'=>$error ));
            } else {
                $adapter->setDestination($path);
                if ($adapter->receive($fileName)) {
                    $game = $this->getGameMapper()->update($game);

                    return $game;
                }
            }
        } else {
            $game = $this->getGameMapper()->update($game);

            return $game;
        }*/

        $game = $this->getGameMapper()->update($game);
        // If I receive false, it means that the FB Id was not available anymore
        $result = $this->getEventManager()->trigger(__FUNCTION__.'.post', $this, array('game' => $game));

        return $game;
    }

    /**
     * getActiveGames
     *
     * @return Array of AdfabGame\Entity\Game
     */
    public function getActiveGames($displayHome = true, $classType='', $order='')
    {
        $em = $this->getServiceManager()->get('zfcuser_doctrine_em');
        $today = new \DateTime("now");
        //$today->format('Y-m-d H:i:s');
        $today = $today->format('Y-m-d') . ' 23:59:59';

        $classClause='';
        $displayHomeClause='';
        $orderBy ='publicationDate';

        if ($classType != '') {
            $classClause = " AND g.classType = '" . $classType . "'";
        }
        if ($displayHome) {
            $displayHomeClause = " AND g.displayHome = true";
        }

        if ($order != '') {
            $orderBy = $order;
        }

        // Game active with a startDate before today (or without startDate) and closeDate after today (or without closeDate)
        $query = $em->createQuery(
            'SELECT g FROM AdfabGame\Entity\Game g
                WHERE (g.publicationDate <= :date OR g.publicationDate IS NULL)
                AND (g.closeDate >= :date OR g.closeDate IS NULL)
                AND g.active = 1
                AND g.broadcastPlatform = 1'
                . $displayHomeClause
                . $classClause
                .' ORDER BY g.'
                . $orderBy
                . ' DESC'
        );
        $query->setParameter('date', $today);
        $games = $query->getResult();

        //je les classe par date de publication (date comme clé dans le tableau afin de pouvoir merger les objets
        // de type article avec le même procédé en les classant naturellement par date asc ou desc
       $arrayGames = array();
       foreach ($games as $game) {
           if ($game->getPublicationDate()) {
               $key = $game->getPublicationDate()->format('Ymd').$game->getUpdatedAt()->format('Ymd').'-'.$game->getId();
           } elseif ($game->getStartDate()) {
               $key = $game->getStartDate()->format('Ymd') . $game->getUpdatedAt()->format('Ymd').'-'.$game->getId();
           } else {
               $key = $game->getUpdatedAt()->format('Ymd') . $game->getUpdatedAt()->format('Ymd').'-'.$game->getId();
           }
           $arrayGames[$key] = $game;
       }

        return $arrayGames;
    }

    /**
     * getAvailableGames : Games OnLine and not already played by $user
     *
     * @return Array of AdfabGame\Entity\Game
     */
    public function getAvailableGames($user, $maxResults = 2)
    {
        $em = $this->getServiceManager()->get('zfcuser_doctrine_em');
        $today = new \DateTime("now");
        //$today->format('Y-m-d H:i:s');
        $today = $today->format('Y-m-d') . ' 23:59:59';

        // Game active with a start_date before today (or without start_date) and end_date after today (or without end-date)
        $query = $em->createQuery(
            'SELECT g FROM AdfabGame\Entity\Game g
                WHERE NOT EXISTS (SELECT l FROM AdfabGame\Entity\Entry l WHERE l.game = g AND l.user = :user)
                AND (g.startDate <= :date OR g.startDate IS NULL)
                AND (g.endDate >= :date OR g.endDate IS NULL)
                AND g.active = 1 AND g.broadcastPlatform = 1
                ORDER BY g.startDate ASC'
        );
        $query->setParameter('date', $today);
        $query->setParameter('user', $user);
        $query->setMaxResults($maxResults);
        $games = $query->getResult();

        return $games;
    }

    /**
     * getActiveSliderGames
     *
     * @return Array of AdfabGame\Entity\Game
     */
    public function getActiveSliderGames()
    {
        $em = $this->getServiceManager()->get('zfcuser_doctrine_em');
        $today = new \DateTime("now");
        //$today->format('Y-m-d H:i:s');
        $today = $today->format('Y-m-d') . ' 23:59:59';

        // Game active with a start_date before today (or without start_date) and end_date after today (or without end-date)
        $query = $em->createQuery(
            'SELECT g FROM AdfabGame\Entity\Game g
            WHERE (g.publicationDate <= :date OR g.publicationDate IS NULL)
            AND (g.closeDate >= :date OR g.closeDate IS NULL)
            AND g.active = true AND g.broadcastPlatform = 1 AND g.pushHome = true'
        );
        $query->setParameter('date', $today);
        $games = $query->getResult();

        //je les classe par date de publication (date comme clé dans le tableau afin de pouvoir merger les objets
        // de type article avec le même procédé en les classant naturellement par date asc ou desc
        $arrayGames = array();
        foreach ($games as $game) {
            if ($game->getPublicationDate()) {
                $key = $game->getPublicationDate()->format('Ymd').$game->getUpdatedAt()->format('Ymd').'-'.$game->getId();
            } elseif ($game->getStartDate()) {
                $key = $game->getStartDate()->format('Ymd') . $game->getUpdatedAt()->format('Ymd').'-'.$game->getId();
            } else {
                $key = $game->getUpdatedAt()->format('Ymd') . $game->getUpdatedAt()->format('Ymd').'-'.$game->getId();
            }
            $arrayGames[$key] = $game;
        }

        return $arrayGames;
    }

    /**
     * getPrizeCategoryGames
     *
     * @return Array of AdfabGame\Entity\Game
     */
    public function getPrizeCategoryGames($categoryid)
    {
        $em = $this->getServiceManager()->get('zfcuser_doctrine_em');

        $query = $em->createQuery(
            'SELECT g FROM AdfabGame\Entity\Game g
            WHERE (g.prizeCategory = :categoryid AND g.broadcastPlatform = 1)
            ORDER BY g.publicationDate DESC'
        );
        $query->setParameter('categoryid', $categoryid);
        $games = $query->getResult();

        return $games;
    }

    public function checkGame($identifier, $checkIfStarted=true)
    {
        $gameMapper = $this->getGameMapper();
        $gameEntity = $this->getGameEntity();
        $today      = new \Datetime('now');

        if (!$identifier) {
            return false;
        }

        $game = $gameMapper->findByIdentifier($identifier);

        // the game has not been found
        if (!$game) {
            return false;
        }

        // The game is inactive
        if (!$game->getActive()) {
            return false;
        }

        // the game is not of the right type
        if (!$game instanceof $gameEntity) {
            return false;
        }

        // the game has not begun yet
        if (!$game->isOpen()) {
            return false;
        }

        // the game is finished and closed
        if (!$game->isStarted() && $checkIfStarted) {
            return false;
        }

        return $game;
    }

    /**
     * deprecated
     * @param  unknown $game
     * @param  string  $user
     * @return boolean
     */
/*    public function checkSubscription($game, $user=null)
    {
        $mapperLeader = $this->getLeaderBoardService()->getLeaderBoardMapper();
        $leaderBoard  = false;
        // Is the user logged in ?
        if ($user) {
            $leaderBoard = $mapperLeader->findOneBy(array('game' => $game, 'user' => $user));
        }

        return $leaderBoard;
    }*/

    /**
     * deprecated
     * @param  unknown         $game
     * @param  unknown         $user
     * @return boolean|unknown
     */
 /*   public function subscribe($game, $user)
    {
        $mapperLeader = $this->getLeaderBoardService()->getLeaderBoardMapper();
        $subscription  = $this->checkSubscription($game, $user);
        // Is the user logged in and not yet registered ?

        if (!$subscription && $user) {
            // The user is not registered, we register him
            $leaderBoard = new \AdfabGame\Entity\LeaderBoard($user, $game);
            try {
                $subscription = $this->getLeaderBoardService()->create($leaderBoard);
                $this->getEventManager()->trigger(__FUNCTION__.'.post', $this, array('user' => $user, 'game' => $game));
            } catch (Exception $e) {
                return false;
            }
        }

        return $subscription;
    }
*/
    /**
     * Return the last entry of the user on this game, if it exists
     * If the param active is set, it can check if the entry is active or not.
     * @param  unknown $game
     * @param  string  $user
     * @return boolean
     */
    public function checkExistingEntry($game, $user=null, $active=null)
    {
        $entryMapper = $this->getEntryMapper();
        $entry = false;

        if (! is_null($active)) {
            $search = array('game' => $game, 'user' => $user, 'active' => $active);
        } else {
            $search = array('game' => $game, 'user' => $user);
        }

        if ($user) {
            $entry = $entryMapper->findOneBy($search);
        }

        return $entry;
    }

    /**
     * errors :
     *     -1 : user not connected
     *     -2 : limit entry games for this user reached
     *
     * @param  AdfabGame\Entity\Game $game
     * @param  unknown               $user
     * @return number|unknown
     */
    public function play($game, $user)
    {
        $entryMapper = $this->getEntryMapper();

        // certaines participations peuvent rester ouvertes. On autorise alors le joueur à reprendre là ou il en était
        // par exemple les postvote...
        $entry = $this->checkExistingEntry($game, $user, true);

        if (! $entry) {
            // je regarde s'il y a une limitation sur le jeu
            $limitAmount = $game->getPlayLimit();
            if ($limitAmount) {
                $limitScale  = $game->getPlayLimitScale();
                $userEntries = $this->getEntryMapper()->findLastEntriesBy($game, $user, $limitScale);

                // player has reached the game limit
                if ($userEntries >= $limitAmount) {
                    return false;
                }
            }

            $entry = new Entry();
            $entry->setGame($game);
            $entry->setUser($user);
            $entry->setPoints(0);

            $entry = $entryMapper->insert($entry);
            $this->getEventManager()->trigger(__FUNCTION__.'.post', $this, array('user' => $user, 'game' => $game));
        }

        return $entry;
    }

    public function sendShareMail($data, $game, $user, $template = 'share_game', $subject = 'Club Metro', $topic = NULL)
    {

        $mailService = $this->getServiceManager()->get('adfabgame_message');
        $mailSent    = false;
        $config 	 = $this->getServiceManager()->get('config');
        $from 		 = $user->getFirstName() .' '. $user->getLastName() .' <'.$config['adfabuser']['email_from_address']['email'].'>';
        $secretKey   = strtoupper(substr(sha1($user->getId().'####'.time()),0,15));


        if (!$topic) {
            $topic = $game->getTitle();
        }

        if ($data['email1']) {
            $mailSent = true;
            $message = $mailService->createHtmlMessage($from, $data['email1'], $subject, 'adfab-game/frontend/email/'.$template, array('game' => $game, 'email' => $user->getEmail(), 'secretKey' => $secretKey));
            $mailService->send($message);
        }
        if ($data['email2'] && $data['email2'] != $data['email1']) {
            $mailSent = true;
            $message = $mailService->createHtmlMessage($from, $data['email2'], $subject, 'adfab-game/frontend/email/'.$template, array('game' => $game, 'email' => $user->getEmail(), 'secretKey' => $secretKey));
            $mailService->send($message);
        }
        if ($data['email3'] && $data['email3'] != $data['email2'] && $data['email3'] != $data['email1']) {
            $mailSent = true;
            $message = $mailService->createHtmlMessage($from, $data['email3'], $subject, 'adfab-game/frontend/email/'.$template, array('game' => $game, 'email' => $user->getEmail(), 'secretKey' => $secretKey));
            $mailService->send($message);
        }
        if ($mailSent) {
            $this->getEventManager()->trigger(__FUNCTION__.'.post', $this, array('user' => $user, 'topic' => $topic, 'secretKey' => $secretKey));

            return true;
        }

        return false;
    }

    public function sendGameMail($game, $user, $post, $template = 'postvote')
    {

        $mailService = $this->getServiceManager()->get('adfabgame_message');
        $from 		 = '';
        $to          = $user->getEmail();
        $subject 	 = 'Club Metro';

        $config 	 = $this->getServiceManager()->get('config');

        if (isset($config['contact']['email'])) {
            $from = $config['contact']['email'];
        }

        $message = $mailService->createHtmlMessage($from, $to, $subject, 'adfab-game/frontend/email/'.$template, array('game' => $game, 'post' => $post));
        $mailService->send($message);
    }

    public function postFbWall($secretKey, $game, $user, $topic = NULL)
    {
        if (!$topic) {
            $topic = $game->getTitle();
        }

        $this->getEventManager()->trigger(__FUNCTION__.'.post', $this, array('user' => $user, 'secretKey' => $secretKey, 'topic' => $topic));

        return true;
    }

    public function postFbRequest($secretKey, $game, $user)
    {
        $this->getEventManager()->trigger(__FUNCTION__.'.post', $this, array('user' => $user, 'game' => $game, 'secretKey' => $secretKey));

        return true;
    }

    public function postTwitter($secretKey, $game, $user, $topic = NULL)
    {
        if (!$topic) {
            $topic = $game->getTitle();
        }

        $this->getEventManager()->trigger(__FUNCTION__.'.post', $this, array('user' => $user, 'secretKey' => $secretKey, 'topic' => $topic));

        return true;
    }

    public function postGoogle($secretKey, $game, $user, $topic = NULL)
    {
        if (!$topic) {
            $topic = $game->getTitle();
        }

        $this->getEventManager()->trigger(__FUNCTION__.'.post', $this, array('user' => $user, 'secretKey' => $secretKey, 'topic' => $topic));

        return true;
    }

    /**
     * Is it possible to trigger a bonus entry ?
     * @param unknown_type $game
     * @param unknown_type $user
     */
    public function allowBonus($game, $user)
    {
        $entryMapper = $this->getEntryMapper();

        if (!$game->getPlayBonus() || $game->getPlayBonus() == 'none') {
            return false;
        } elseif ($game->getPlayBonus() == 'one') {
            if ($entryMapper->findOneBy(array('game' => $game, 'user' => $user, 'bonus' => 1))) {
                return false;
            }
        } elseif ($game->getPlayBonus() == 'per_entry') {
            return $entryMapper->checkBonusEntry($game,$user);
        }

        return true;
    }

    /**
     * This bonus entry doesn't give points nor badges
     * It's just there to increase the chances during the Draw
     *
     * @param  AdfabGame\Entity\Game $game
     * @param  unknown               $user
     * @return number|unknown
     */
    public function playBonus($game, $user, $winner = 0)
    {
        $entryMapper = $this->getEntryMapper();

        if ($this->allowBonus($game, $user)) {
            $entry = new Entry();
            $entry->setGame($game);
            $entry->setUser($user);
            $entry->setPoints(0);
            $entry->setActive(0);
            $entry->setBonus(1);
            $entry->setWinner($winner);

            $entry = $entryMapper->insert($entry);

            return true;
        }

        return false;
    }

    //TODO : Terminer et Refactorer afin de le mettre dans AdfabCore
    public static function cronMail()
    {
        //TODO : factoriser la config
        $configuration = array(
                'modules' => array(
                        'Application',
                        'DoctrineModule',
                        'DoctrineORMModule',
                        'ZfcBase',
                        'ZfcUser',
                        'BjyAuthorize',
                        'ZfcAdmin',
                        'AdfabCore',
                        'AdfabUser',
                        'AdfabCms',
                        'AdfabReward',
                        'AdfabGame',
                        'AdfabPartnership'
                ),
                'module_listener_options' => array(
                        'config_glob_paths'    => array(
                                'config/autoload/{,*.}{global,local}.php',
                        ),
                        'module_paths' => array(
                                './module',
                                './vendor',
                        ),
                ),
        );
        $smConfig = isset($configuration['service_manager']) ? $configuration['service_manager'] : array();
        $sm = new \Zend\ServiceManager\ServiceManager(new \Zend\Mvc\Service\ServiceManagerConfig($smConfig));
        $sm->setService('ApplicationConfig', $configuration);
        $sm->get('ModuleManager')->loadModules();
        $sm->get('Application')->bootstrap();

        $mailService = $sm->get('adfabuser_message');
        $gameService = $sm->get('adfabgame_quiz_service');
        $options = $sm->get('adfabgame_module_options');

        $from    = "admin@playground.fr";//$options->getEmailFromAddress();
        $subject = "sujet game"; //$options->getResetEmailSubjectLine();

        $to = "gbesson@test.com";

        $game = $gameService->checkGame('qooqo');

        // On recherche les joueurs qui n'ont pas partagé leur qquiz après avoir joué
        // entry join user join game : distinct user et game et game_entry = 0 et updated_at <= jour-1 et > jour - 2
        //$contacts = getQuizUsersNotSharing();

        //foreach ($contacts as $contact) {
            //$message = $mailService->createTextMessage('titi@test.com', 'gbesson@test.com', 'sujetcron', 'adfab-user/email/forgot', array());
            $message = $mailService->createTextMessage($from, $to, $subject, 'adfab-game/frontend/email/share_reminder', array('game' => $game));

            $mailService->send($message);
        //}

    }

    public function uploadFile($path, $file)
    {
        $err = $file["error"];
        $message='';
        if ($err > 0) {
            switch ($err) {
                case '1':
                    $message.='Max file size exceeded. (php.ini)';
                    break;
                case '2':
                    $message.='Max file size exceeded.';
                    break;
                case '3':
                    $message.='File upload was only partial.';
                    break;
                case '4':
                    $message.='No file was attached.';
                    break;
                case '7':
                    $message.='File permission denied.';
                    break;
                default :
                    $message.='Unexpected error occurs.';
            }

            return $err;
        } else {

            if (file_exists($path.$file["name"])) {
                $message.='File already exist';

                return $file["name"];
            } else {
                $adapter = new \Zend\File\Transfer\Adapter\Http();
                // 400ko
                $size = new Size(array('max'=>400000));
                $is_image = new IsImage('jpeg,png,gif,jpg');
                $adapter->setValidators(array($size, $is_image), $value['name']);

                if (!$adapter->isValid()) {
                    $dataError = $adapter->getMessages();
                    $error = array();
                    foreach ($dataError as $key=>$row) {
                        $error[] = $row;
                    }

                    return false;
                }
                @move_uploaded_file($file["tmp_name"],$path.$file["name"]);
            }
        }

        return $file["name"];
    }

    public function findBy($array, $sort)
    {
         return $this->getGameMapper()->findBy($array, $sort);
    }

    public function findAll()
    {
        return $this->getGameMapper()->findAll();
    }

    public function findAllEntry()
    {
        return $this->getEntryMapper()->findAll();
    }

    public function GetOrderType($type= '', $order='DESC')
    {
        switch ($type) {
            case 'beginDate' :
                $orderType = array('startDate' => $order);
                break;
            case 'activeGame' :
                $orderType = array('active' => $order);
                break;
            case 'createdDate' :
                $orderType = array('createdAt' => $order);
                break;
        }

        return $this->getGameMapper()->findBy(array(), $orderType);
    }

    /**
     * getGameMapper
     *
     * @return GameMapperInterface
     */
    public function getGameMapper()
    {
        if (null === $this->gameMapper) {
            $this->gameMapper = $this->getServiceManager()->get('adfabgame_game_mapper');
        }

        return $this->gameMapper;
    }

    /**
     * setGameMapper
     *
     * @param  GameMapperInterface $gameMapper
     * @return User
     */
    public function setGameMapper(GameMapperInterface $gameMapper)
    {
        $this->gameMapper = $gameMapper;

        return $this;
    }

    /**
     * getEntryMapper
     *
     * @return EntryMapperInterface
     */
    public function getEntryMapper()
    {
        if (null === $this->entryMapper) {
            $this->entryMapper = $this->getServiceManager()->get('adfabgame_entry_mapper');
        }

        return $this->entryMapper;
    }

    /**
     * setEntryMapper
     *
     * @param  EntryMapperInterface $entryMapper
     * @return Entry
     */
    public function setEntryMapper($entryMapper)
    {
        $this->entryMapper = $entryMapper;

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

    public function getLeaderBoardService()
    {
        if (!$this->leaderBoardService) {
            $this->leaderBoardService = $this->getServiceManager()->get('adfabgame_leaderboard_service');
        }

        return $this->leaderBoardService;
    }

    public function setLeaderBoardService(LeaderBoardService $leaderBoardService)
    {
        $this->leaderBoardService = $leaderBoardService;

        return $this;
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
     * @return Game
     */
    public function setServiceManager(ServiceManager $serviceManager)
    {
        $this->serviceManager = $serviceManager;

        return $this;
    }

    public function getExtension($str)
    {
        $i = strrpos($str,'.');

        $l = strlen($str) - $i;
        $ext = substr($str,$i+1,$l);

        return $ext;
    }

    public function get_src($extension,$temp_path)
    {
         $image_src = '';
         switch ($extension) {
             case 'jpg':
                 $image_src = imagecreatefromjpeg($temp_path);
                 break;
             case 'jpeg':
                 $image_src = imagecreatefromjpeg($temp_path);
                 break;
             case 'png':
                 $image_src = imagecreatefrompng($temp_path);
                 break;
             case 'gif':
                 $image_src = imagecreatefromgif($temp_path);
                 break;
         }

         return $image_src;
     }

    public function resize( $tmp_file, $extension, $rep, $src,$mini_width, $mini_height )
    {
        list( $src_width,$src_height ) = getimagesize($tmp_file);

        $ratio_src = $src_width / $src_height;
        $ratio_mini = $mini_width / $mini_height;

        if ($ratio_src >= $ratio_mini) {
           $new_height_mini = $mini_height;
           $new_width_mini = $src_width / ($src_height / $mini_height);

        } else {
           $new_width_mini = $mini_width;
           $new_height_mini = $src_height / ($src_width / $mini_width);
        }

        $new_image_mini = imagecreatetruecolor($mini_width, $mini_height);

        imagecopyresampled($new_image_mini, $src,
                           0 - ($new_width_mini - $mini_width) / 2,
                           0 - ($new_height_mini - $mini_height) / 2,
                           0, 0,
                           $new_width_mini, $new_height_mini,
                           $src_width, $src_height);
        imagejpeg($new_image_mini, $rep);

        imagedestroy($new_image_mini);

    }
}
