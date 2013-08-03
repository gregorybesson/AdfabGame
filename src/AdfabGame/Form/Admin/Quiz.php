<?php

namespace AdfabGame\Form\Admin;

use Zend\Form\Form;
use Zend\Form\Element;
use Zend\I18n\Translator\Translator;
use AdfabCore\Stdlib\Hydrator\DoctrineObject as DoctrineHydrator;
use Zend\ServiceManager\ServiceManager;

class Quiz extends Game
{
    public function __construct($name = null, ServiceManager $sm, Translator $translator)
    {
        $this->setServiceManager($sm);
        $entityManager = $sm->get('doctrine.entitymanager.orm_default');

        // Mapping of an Entity to get value by getId()... Should be taken in charge by Doctrine Hydrator Strategy...
        // having to fix a DoctrineModule bug :( https://github.com/doctrine/DoctrineModule/issues/180
        $hydrator = new DoctrineHydrator($entityManager, 'AdfabGame\Entity\Quiz');
        $hydrator->addStrategy('partner', new \AdfabCore\Stdlib\Hydrator\Strategy\ObjectStrategy());
        $this->setHydrator($hydrator);

        parent::__construct($name, $sm, $translator);

        $entityManager = $sm->get('doctrine.entitymanager.orm_default');

        $this->add(array(
                'type' => 'Zend\Form\Element\Select',
                'name' => 'drawAuto',
                'attributes' =>  array(
                        'id' => 'drawAuto',
                        'options' => array(
                                '0' => $translator->translate('Non', 'adfabgame'),
                                '1' => $translator->translate('Oui', 'adfabgame'),
                        ),
                ),
                'options' => array(
                        'label' => $translator->translate('Tirage au sort automatique', 'adfabgame'),
                ),
        ));

        $this->add(array(
                'name' => 'winners',
                'options' => array(
                        'label' => $translator->translate('Nombre de gagnants', 'adfabgame')
                ),
                'attributes' => array(
                        'type' => 'text',
                        'placeholder' => $translator->translate('Nombre de gagnants', 'adfabgame')
                )
        ));

        $this->add(array(
                'name' => 'substitutes',
                'options' => array(
                        'label' => $translator->translate('Nombre de remplaçants', 'adfabgame')
                ),
                'attributes' => array(
                        'type' => 'text',
                        'placeholder' => $translator->translate('Nombre de remplaçants', 'adfabgame')
                )
        ));

        $this->add(array(
            'name' => 'timer',
            'type' => 'Zend\Form\Element\Radio',
            'attributes' => array(
                'required' => 'required',
                'value' => '0',
            ),
            'options' => array(
                'label' => 'Use a Timer',
                'value_options' => array(
                    '0' => 'No',
                    '1' => 'yes',
                ),
            ),
        ));

        $this->add(array(
            'name' => 'timerDuration',
            'type' => 'Zend\Form\Element\Text',
            'attributes' => array(
                'placeholder' => 'Duration in seconds',
            ),
            'options' => array(
                'label' => 'Timer Duration',
            ),
        ));

        $this->add(array(
            'name' => 'victoryConditions',
            'type' => 'Zend\Form\Element\Text',
            'attributes' => array(
                'placeholder' => '% de bonnes réponses',
                'id' => 'victoryConditions'
            ),
            'options' => array(
                'label' => 'Conditions de victoire',
            ),
        ));

        $this->add(array(
            'name' => 'questionGrouping',
            'type' => 'Zend\Form\Element\Text',
            'attributes' => array(
                'required' => 'required',
            ),
            'options' => array(
                'label' => 'Group question',
            ),
        ));

        /*$this->add(array(
                'type' => 'Zend\Form\Element\Collection',
                'name' => 'questions',
                'options' => array(
                        'label' => 'Please create questions',
                        'count' => 2,
                        'should_create_template' => true,
                        'allow_add' => true,
                        'target_element' => array(
                            'type' => 'AdfabGame\Form\Admin\QuizQuestionFieldset'
                        )
                )
        ));*/

    }
}
