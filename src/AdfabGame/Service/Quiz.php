<?php

namespace AdfabGame\Service;

use AdfabGame\Entity\QuizReply;

use AdfabGame\Entity\Entry;

use Zend\Form\Form;
use Zend\ServiceManager\ServiceManagerAwareInterface;
use Zend\ServiceManager\ServiceManager;
use AdfabGame\Mapper\GameInterface as GameMapperInterface;
use Zend\Stdlib\ErrorHandler;

class Quiz extends Game implements ServiceManagerAwareInterface
{

    /**
     * @var QuizMapperInterface
     */
    protected $quizMapper;

    /**
     * @var QuizAnswerMapperInterface
     */
    protected $quizAnswerMapper;

    /**
     * @var QuizQuestionMapperInterface
     */
    protected $quizQuestionMapper;

    /**
     * @var QuizReplyMapperInterface
     */
    protected $quizReplyMapper;

    /**
     *
     *
     * @param  array                  $data
     * @param  string                 $entityClass
     * @param  string                 $formClass
     * @return \AdfabGame\Entity\Game
     */
    public function createQuestion(array $data)
    {
        $path = $this->getOptions()->getMediaPath() . DIRECTORY_SEPARATOR;
        $media_url = $this->getOptions()->getMediaUrl() . '/';

        $question  = new \AdfabGame\Entity\QuizQuestion();
        $form  = $this->getServiceManager()->get('adfabgame_quizquestion_form');
        $form->bind($question);
        $form->setData($data);

        $quiz = $this->getGameMapper()->findById($data['quiz_id']);

        if (!$form->isValid()) {
            return false;
        }

        $question->setQuiz($quiz);

        // Max points and correct answers calculation for the question
        $question = $this->calculateMaxAnswersQuestion($question);

        // Max points and correct answers recalculation for the quiz
        $quiz = $this->calculateMaxAnswersQuiz($question->getQuiz());

        $this->getEventManager()->trigger(__FUNCTION__, $this, array('game' => $question, 'data' => $data));
        $this->getQuizQuestionMapper()->insert($question);
        $this->getEventManager()->trigger(__FUNCTION__.'.post', $this, array('game' => $question, 'data' => $data));

        if (!empty($data['upload_image']['tmp_name'])) {
            ErrorHandler::start();
            $data['upload_image']['name'] = $this->fileNewname($path, $question->getId() . "-" . $data['upload_image']['name']);
            move_uploaded_file($data['upload_image']['tmp_name'], $path . $data['upload_image']['name']);
            $question->setImage($media_url . $data['upload_image']['name']);
            ErrorHandler::stop(true);
        }

        $this->getQuizQuestionMapper()->update($question);
        $this->getQuizMapper()->update($quiz);

        return $question;
    }

    /**
     * @param  array                  $data
     * @param  string                 $entityClass
     * @param  string                 $formClass
     * @return \AdfabGame\Entity\Game
     */
    public function updateQuestion(array $data, $question)
    {
        $path = $this->getOptions()->getMediaPath() . DIRECTORY_SEPARATOR;
        $media_url = $this->getOptions()->getMediaUrl() . '/';

        $form  = $this->getServiceManager()->get('adfabgame_quizquestion_form');
        $form->bind($question);
        $form->setData($data);

        if (!$form->isValid()) {
            return false;
        }

        if (!empty($data['upload_image']['tmp_name'])) {
            ErrorHandler::start();
			$data['upload_image']['name'] = $this->fileNewname($path, $question->getId() . "-" . $data['upload_image']['name']);
            move_uploaded_file($data['upload_image']['tmp_name'], $path . $data['upload_image']['name']);
            $question->setImage($media_url . $data['upload_image']['name']);
            ErrorHandler::stop(true);
        }

        // Max points and correct answers calculation for the question
        $question = $this->calculateMaxAnswersQuestion($question);

        // Max points and correct answers recalculation for the quiz
        $quiz = $this->calculateMaxAnswersQuiz($question->getQuiz());

        // If the question was a pronostic, I update entries with the results !
        if ($question->getPrediction()) {
            // je recherche toutes les participations au jeu
            $entries = $this->getEntryMapper()->findByGameId($question->getQuiz());

            $answers = $question->getAnswers();
            $answersarray = array();
            foreach ($answers as $answer) {
                $answersarray[$answer->getId()] = $answer;
            }

            // I update all answers with points and correctness
            foreach ($entries as $entry) {
                $quizReplies = $this->getQuizReplyMapper()->findByEntryAndQuestion($entry, $question->getId());
                $quizPoints = 0;
                $quizCorrectAnswers = 0;
                if ($quizReplies) {
                    foreach ($quizReplies as $quizReply) {
                        if ($answersarray[$quizReply->getAnswerId()]) {
                            $updatedAnswer = $answersarray[$quizReply->getAnswerId()];

                            $quizReply->setPoints($updatedAnswer->getPoints());
                            $quizPoints += $updatedAnswer->getPoints();
                            $quizReply->setCorrect($updatedAnswer->getCorrect());
                            $quizCorrectAnswers += $updatedAnswer->getCorrect();
                            $this->getQuizReplyMapper()->update($quizReply);
                        }
                    }
                }

                $winner = $this->isWinner($quiz, $quizCorrectAnswers);

                $entry->setWinner($winner);
                $entry->setPoints($quizPoints);
                $entry->setActive(false);
                $entry = $this->getEntryMapper()->update($entry);
            }

            $this->getEventManager()->trigger(__FUNCTION__.'.prediction', $this, array('question' => $question, 'data' => $data));
        }

        $this->getEventManager()->trigger(__FUNCTION__, $this, array('question' => $question, 'data' => $data));
        $this->getQuizQuestionMapper()->update($question);
        $this->getEventManager()->trigger(__FUNCTION__.'.post', $this, array('question' => $question, 'data' => $data));

        $this->getQuizMapper()->update($quiz);

        return $question;
    }

    public function calculateMaxAnswersQuestion($question)
    {
        $question_max_points = 0;
        $question_max_correct_answers = 0;
        // Closed question : Only one answer allowed
        if ($question->getType() == 0) {
            foreach ($question->getAnswers() as $answer) {
                if ($answer->getPoints() > $question_max_points) {
                    $question_max_points = $answer->getPoints();
                }
                if ( $answer->getCorrect() && $question_max_correct_answers==0) {
                    $question_max_correct_answers=1;
                }
            }
        // Closed question : Many answers allowed
        } elseif ($question->getType() == 1) {
            foreach ($question->getAnswers() as $answer) {

                $question_max_points += $answer->getPoints();

                if ( $answer->getCorrect() ) {
                    ++$question_max_correct_answers;
                }
            }
        // Not a question : A textarea to fill in
        } elseif ($question->getType() == 2) {
            $question_max_correct_answers = 0;
        }

        $question->setMaxPoints($question_max_points);
        $question->setMaxCorrectAnswers($question_max_correct_answers);

        // echo "Quiz Max Points : " . $quiz->getMaxPoints()  . "Quiz  Max correct : " . $quiz->getMaxCorrectAnswers();
        // echo "Max Points : " . $question->getMaxPoints() . "  max correct : " . $question->getMaxCorrectAnswers();
        // die();
        return $question;
    }

    public function calculateMaxAnswersQuiz($quiz)
    {
        $question_max_points = 0;
        $question_max_correct_answers = 0;
        foreach ($quiz->getQuestions() as $question) {
            $question_max_points += $question->getMaxPoints();
            $question_max_correct_answers += $question->getMaxCorrectAnswers();
        }
        $quiz->setMaxPoints($question_max_points);
        $quiz->setMaxCorrectAnswers($question_max_correct_answers);

        return $quiz;
    }

    public function getNumberCorrectAnswersQuiz($user, $count='count')
    {
        $em = $this->getServiceManager()->get('zfcuser_doctrine_em');

        if ($count == 'count') {
            $aggregate = 'COUNT(e.id)';
        }

        $query = $em->createQuery(
            'SELECT '.$aggregate.' FROM AdfabGame\Entity\Entry e, AdfabGame\Entity\Game g
                WHERE e.user = :user
                AND g.classType = :quiz
                AND e.points > 0'
        );
        $query->setParameter('user', $user);
        $query->setParameter('quiz', 'quiz');
        $number = $query->getSingleScalarResult();

        return $number;
    }

    public function createQuizReply($data, $game, $user)
    {
        // Si mon nb de participation est < au nb autorisé, j'ajoute une entry + reponses au quiz et points
        $quizReplyMapper = $this->getQuizReplyMapper();
        $entryMapper = $this->getEntryMapper();
        $entry = $entryMapper->findLastActiveEntryById($game, $user);

        if (!$entry) {
            return false;
        }

        $quizPoints          = 0;
        $quizCorrectAnswers  = 0;
        $ratioCorrectAnswers = 0;
        $maxCorrectAnswers = $game->getMaxCorrectAnswers();

        foreach ($data as $group) {
            foreach ($group as $q => $a) {
                $question = $this->getQuizQuestionMapper()->findById((int) str_replace('q', '', $q));
                if (is_array($a)) {
                    foreach ($a as $k => $answer_id) {
                        $answer = $this->getQuizAnswerMapper()->findById($answer_id);
                        if ($answer) {
                            $quizReply = new QuizReply();
                            $quizReply->setAnswer($answer->getAnswer());
                            $quizReply->setAnswerId($answer_id);
                            $quizReply->setQuestion($question->getQuestion());
                            $quizReply->setQuestionId($question->getId());
                            $quizReply->setPoints($answer->getPoints());
                            $quizReply->setCorrect($answer->getCorrect());
                            $quizReply->setEntry($entry);

                            $quizReplyMapper->insert($quizReply);
                            $quizPoints += $answer->getPoints();
                            $quizCorrectAnswers += $answer->getCorrect();
                        }
                    }
                } elseif ($question->getType() == 0 || $question->getType() == 1) {
                    $answer = $this->getQuizAnswerMapper()->findById($a);
                    if ($answer) {
                        $quizReply = new QuizReply();
                        $quizReply->setAnswer($answer->getAnswer());
                        $quizReply->setAnswerId($a);
                        $quizReply->setQuestion($question->getQuestion());
                        $quizReply->setQuestionId($question->getId());
                        $quizReply->setPoints($answer->getPoints());
                        $quizReply->setCorrect($answer->getCorrect());
                        $quizReply->setEntry($entry);

                        $quizReplyMapper->insert($quizReply);
                        $quizPoints += $answer->getPoints();
                        $quizCorrectAnswers += $answer->getCorrect();
                    }
                } elseif ($question->getType() == 2) {
                    $quizReply = new QuizReply();

                    //TODO sanitize answer
                    $quizReply->setAnswer($a);
                    $quizReply->setAnswerId(0);
                    $quizReply->setQuestion($question->getQuestion());
                    $quizReply->setQuestionId($question->getId());
                    $quizReply->setPoints(0);
                    $quizReply->setCorrect(0);
                    $quizReply->setEntry($entry);

                    $quizReplyMapper->insert($quizReply);
                    $quizPoints += 0;
                    $quizCorrectAnswers += 0;
                }
            }
        }

        $winner = $this->isWinner($game, $quizCorrectAnswers);

        $entry->setWinner($winner);
        $entry->setPoints($quizPoints);
        $entry->setActive(false);
        $entry = $entryMapper->update($entry);

        // event used to trigger the points won by customer (PlaygroundReward is listening !)
        if ($quizCorrectAnswers > 0) {
            $this->getEventManager()->trigger(__FUNCTION__.'.post', $this, array('user' => $user, 'game' => $game, 'data' => $quizCorrectAnswers));
        }

        return $entry;
    }

    public function isWinner($game, $quizCorrectAnswers=0)
    {
        // Pour déterminer le gagnant, je regarde le nombre max de reponses correctes possibles
        // dans le jeu, puis je calcule le ratio de bonnes réponses et le compare aux conditions
        // de victoire
        $winner = false;
        $maxCorrectAnswers = $game->getMaxCorrectAnswers();
        if ($maxCorrectAnswers > 0) {
            $ratioCorrectAnswers = ($quizCorrectAnswers / $maxCorrectAnswers) * 100;
        } elseif ($game->getVictoryConditions() > 0) {
            // In the case I have a pronostic game for example
            $ratioCorrectAnswers = 0;
        } else {
            // In the case I want everybody to win
            $ratioCorrectAnswers = 100;
        }

        $winner = false;
        if ($game->getVictoryConditions() >= 0) {
            if ($ratioCorrectAnswers >= $game->getVictoryConditions()) {
                $winner = true;
            }
        }

        return $winner;
    }

    public function getGameEntity()
    {
        return new \AdfabGame\Entity\Quiz;
    }

    /**
     * getQuizMapper
     *
     * @return QuizMapperInterface
     */
    public function getQuizMapper()
    {
        if (null === $this->quizMapper) {
            $this->quizMapper = $this->getServiceManager()->get('adfabgame_quiz_mapper');
        }

        return $this->quizMapper;
    }

    /**
     * setQuizMapper
     *
     * @param  QuizMapperInterface $quizMapper
     * @return Game
     */
    public function setQuizMapper(GameMapperInterface $quizMapper)
    {
        $this->quizMapper = $quizMapper;

        return $this;
    }

    /**
     * getQuizQuestionMapper
     *
     * @return QuizQuestionMapperInterface
     */
    public function getQuizQuestionMapper()
    {
        if (null === $this->quizQuestionMapper) {
            $this->quizQuestionMapper = $this->getServiceManager()->get('adfabgame_quizquestion_mapper');
        }

        return $this->quizQuestionMapper;
    }

    /**
     * setQuizQuestionMapper
     *
     * @param  QuizQuestionMapperInterface $quizquestionMapper
     * @return QuizQuestion
     */
    public function setQuizQuestionMapper($quizquestionMapper)
    {
        $this->quizQuestionMapper = $quizquestionMapper;

        return $this;
    }

    /**
     * setQuizAnswerMapper
     *
     * @param  QuizAnswerMapperInterface $quizAnswerMapper
     * @return QuizAnswer
     */
    public function setQuizAnswerMapper($quizAnswerMapper)
    {
        $this->quizAnswerMapper = $quizAnswerMapper;

        return $this;
    }

    /**
     * getQuizAnswerMapper
     *
     * @return QuizAnswerMapperInterface
     */
    public function getQuizAnswerMapper()
    {
        if (null === $this->quizAnswerMapper) {
            $this->quizAnswerMapper = $this->getServiceManager()->get('adfabgame_quizanswer_mapper');
        }

        return $this->quizAnswerMapper;
    }

    /**
     * getQuizReplyMapper
     *
     * @return QuizReplyMapperInterface
     */
    public function getQuizReplyMapper()
    {
        if (null === $this->quizReplyMapper) {
            $this->quizReplyMapper = $this->getServiceManager()->get('adfabgame_quizreply_mapper');
        }

        return $this->quizReplyMapper;
    }

    /**
     * setQuizReplyMapper
     *
     * @param  QuizReplyMapperInterface $quizreplyMapper
     * @return QuizReply
     */
    public function setQuizReplyMapper($quizreplyMapper)
    {
        $this->quizReplyMapper = $quizreplyMapper;

        return $this;
    }
}