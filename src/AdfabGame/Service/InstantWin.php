<?php

namespace AdfabGame\Service;

use AdfabGame\Mapper\InstantWinOccurrence;

use AdfabGame\Entity\Entry;

use Zend\Form\Form;
use Zend\ServiceManager\ServiceManagerAwareInterface;
use Zend\ServiceManager\ServiceManager;
use Zend\Stdlib\ErrorHandler;

class InstantWin extends Game implements ServiceManagerAwareInterface
{

    /**
     * @var InstantWinOccurrenceMapperInterface
     */
    protected $instantWinOccurrenceMapper;

    /**
     *
     * saving an instantwin image if any
     *
     * @param  array                  $data
     * @param  string                 $entityClass
     * @param  string                 $formClass
     * @return \AdfabGame\Entity\Game
     */
    public function create(array $data, $entity, $formClass)
    {
        $game = parent::create($data, $entity, $formClass);

        if ($game) {
            if (!empty($data['uploadScratchcardImage']['tmp_name'])) {

                $path = $this->getOptions()->getMediaPath() . DIRECTORY_SEPARATOR;
                $media_url = $this->getOptions()->getMediaUrl() . '/';

                ErrorHandler::start();
                move_uploaded_file($data['uploadScratchcardImage']['tmp_name'], $path . $game->getId() . "-" . $data['uploadScratchcardImage']['name']);
                $game->setScratchcardImage($media_url . $game->getId() . "-" . $data['uploadScratchcardImage']['name']);
                ErrorHandler::stop(true);

                $game = $this->getGameMapper()->update($game);
            }

            if ($game->getOccurrenceNumber() && $game->getScheduleOccurrenceAuto()) {
                $this->scheduleOccurrences($game);
            }
        }

        return $game;
    }

    /**
     *
     * saving an instantwin image if any
     *
     * @param  array                  $data
     * @param  string                 $entityClass
     * @param  string                 $formClass
     * @return \AdfabGame\Entity\Game
     */
    public function edit(array $data, $game, $formClass)
    {
        $game = parent::edit($data, $game, $formClass);

        if ($game) {
            if (!empty($data['uploadScratchcardImage']['tmp_name'])) {

                $path = $this->getOptions()->getMediaPath() . DIRECTORY_SEPARATOR;
                $media_url = $this->getOptions()->getMediaUrl() . '/';

                ErrorHandler::start();
                move_uploaded_file($data['uploadScratchcardImage']['tmp_name'], $path . $game->getId() . "-" . $data['uploadScratchcardImage']['name']);
                $game->setScratchcardImage($media_url . $game->getId() . "-" . $data['uploadScratchcardImage']['name']);
                ErrorHandler::stop(true);

                $game = $this->getGameMapper()->update($game);
            }

            if ($game->getOccurrenceNumber() && $game->getScheduleOccurrenceAuto()) {
                $this->scheduleOccurrences($game);
            }
        }

        return $game;
    }

    /**
     * We can create Instant win occurences dynamically
     *
     *
     * @param  array                  $data
     * @param  string                 $entityClass
     * @param  string                 $formClass
     * @return \AdfabGame\Entity\Game
     */
    public function scheduleOccurrences($game)
    {

        // Je recherche tous les IG non gagnés
        $occurences = $this->getInstantWinOccurrenceMapper()->findBy(array('instantwin' => $game));

        $nbOccurencesToCreate = $game->getOccurrenceNumber() - count($occurences);
        if ($nbOccurencesToCreate > 0) {
            $today    = new \DateTime("now");
            $end      = new \DateTime("now");
            $interval = 'P10D';

            if ($game->getStartDate()) {
                $beginning = $game->getStartDate();
            } else {
                $beginning = $today;
            }

            if ($game->getEndDate()) {
                $end = $game->getEndDate();
            } else {
                $end->add(new \DateInterval($interval));
            }

            for ($i=1;$i<=$nbOccurencesToCreate;$i++) {
                $randomDate = $this->getRandomDate($beginning->format('U'), $end->format('U'));
                $randomDate = \DateTime::createFromFormat('Y-m-d H:i:s', $randomDate);
                $occurrence  = new \AdfabGame\Entity\InstantWinOccurrence();
                $occurrence->setInstantwin($game);
                $occurrence->setOccurrenceDate($randomDate);
                $occurrence->setActive(1);

                $this->getInstantWinOccurrenceMapper()->insert($occurrence);
            }
        }

        return true;
    }

    public function getRandomDate($min_date, $max_date)
    {
        $rand_epoch = rand($min_date, $max_date);

        return date('Y-m-d H:i:s', $rand_epoch);
    }

    /**
     *
     *
     * @param  array                  $data
     * @param  string                 $entityClass
     * @param  string                 $formClass
     * @return \AdfabGame\Entity\Game
     */
    public function createOccurrence(array $data)
    {

        $occurrence  = new \AdfabGame\Entity\InstantWinOccurrence();
        $form  = $this->getServiceManager()->get('adfabgame_instantwinoccurrence_form');
        $form->bind($occurrence);
        $form->setData($data);

        $instantwin = $this->getGameMapper()->findById($data['instant_win_id']);

        if (!$form->isValid()) {
            return false;
        }

        $occurrence->setInstantWin($instantwin);
        $this->getInstantWinOccurrenceMapper()->insert($occurrence);

        return $occurrence;
    }

    /**
     * @param  array                  $data
     * @param  string                 $entityClass
     * @param  string                 $formClass
     * @return \AdfabGame\Entity\Game
     */
    public function updateOccurrence(array $data, $occurrence)
    {

        $form  = $this->getServiceManager()->get('adfabgame_instantwinoccurrence_form');
        $form->bind($occurrence);
        $form->setData($data);

        if (!$form->isValid()) {
            return false;
        }

        $this->getInstantWinOccurrenceMapper()->update($occurrence);

        return $occurrence;
    }

    /**
     * return true if the player has won. False otherwise.
     *
     * @param \AdfabGame\Entity\Game $game
     * @param \AdfabUser\Entity\User $user
     *
     * @return boolean
     */
    public function isInstantWinner($game, $user)
    {

        $entryMapper = $this->getEntryMapper();
        $entry = $entryMapper->findLastActiveEntryById($game, $user);
        if (!$entry) {
            return false;
        }

        $instantWinOccurrencesMapper = $this->getInstantWinOccurrenceMapper();
        // si date après date de gain et date de gain encore active alors desactive date de gain, et winner !
        $winner = $instantWinOccurrencesMapper->checkInstantWinByGameId($game, $user);
        // On ferme la participation
        $entry->setActive(false);

        if ($winner) {
            $entry->setWinner(true);
        } else {
            $entry->setPoints(0);
            $entry->setWinner(false);
        }

        $entry = $entryMapper->update($entry);

        return $winner;
    }

    public function getGameEntity()
    {
        return new \AdfabGame\Entity\InstantWin;
    }

    /**
     * getInstantWinOccurrenceMapper
     *
     * @return InstantWinOccurrenceMapperInterface
     */
    public function getInstantWinOccurrenceMapper()
    {
        if (null === $this->instantWinOccurrenceMapper) {
            $this->instantWinOccurrenceMapper = $this->getServiceManager()->get('adfabgame_instantwinoccurrence_mapper');
        }

        return $this->instantWinOccurrenceMapper;
    }

    /**
     * setInstantWinOccurrenceMapper
     *
     * @param  InstantWinOccurrenceMapperInterface $quizquestionMapper
     * @return InstantWinOccurrence
     */
    public function setInstantWinOccurrenceMapper($instantWinOccurrenceMapper)
    {
        $this->instantWinOccurrenceMapper = $instantWinOccurrenceMapper;

        return $this;
    }
}
