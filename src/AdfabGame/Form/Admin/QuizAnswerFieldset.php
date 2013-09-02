<?php

namespace AdfabGame\Form\Admin;

use AdfabGame\Entity\QuizAnswer;
use Zend\Form\Fieldset;
use Zend\Form\Form;
use Zend\Form\Element;
use Zend\I18n\Translator\Translator;
use DoctrineModule\Stdlib\Hydrator\DoctrineObject as DoctrineHydrator;
use Zend\ServiceManager\ServiceManager;

class QuizAnswerFieldset extends Fieldset
{
    public function __construct($name = null,ServiceManager $serviceManager, Translator $translator)
    {
        parent::__construct($name);
        $entityManager = $serviceManager->get('doctrine.entitymanager.orm_default');

        $this->setHydrator(new DoctrineHydrator($entityManager, 'AdfabGame\Entity\QuizAnswer'))
        ->setObject(new QuizAnswer());

        //$this->setLabel('Answer');

        $this->add(array(
            'type' => 'Zend\Form\Element\Hidden',
            'name' => 'id'
        ));

        $this->add(array(
            'type' => 'Zend\Form\Element\Textarea',
            'name' => 'answer',
            'options' => array(
                'label' => $translator->translate('Réponse', 'adfabgame'),
            ),
            'attributes' => array(
                'required' => true,
                'cols' => '10',
                'rows' => '2',
                'id' => 'answer',
            ),
        ));

        $this->add(array(
                'type' => 'Zend\Form\Element\Select',
                'name' => 'correct',
                'options' => array(
                        //'empty_option' => $translator->translate('Is the answer correct ?', 'adfabgame'),
                        'value_options' => array(
                            '0' => $translator->translate('Non', 'adfabgame'),
                            '1' => $translator->translate('Oui', 'adfabgame'),
                        ),
                        'label' => $translator->translate('Bonne réponse', 'adfabgame'),
                ),
        ));

        $this->add(array(
            'name' => 'position',
            'options' => array(
                'label' => 'Position'
            ),
        ));

        $this->add(array(
            'type' => 'Zend\Form\Element\Button',
            'name' => 'remove',
            'options' => array(
                'label' => $translator->translate('Supprimer', 'adfabgame'),
            ),
			'attributes' => array(
				'class' => 'delete-button',
			)
        ));
/*
        $this->add(array(
            //'type' => 'Zend\Form\Element\Hidden',
            'name' => 'explanation',
            'options' => array(
                'label' => 'Explanation'
            ),
        ));

        $this->add(array(
                //'type' => 'Zend\Form\Element\Hidden',
                'name' => 'video',
                'options' => array(
                        'label' => 'Video'
                ),
        ));

        $this->add(array(
                //'type' => 'Zend\Form\Element\Hidden',
                'name' => 'image',
                'options' => array(
                        'label' => 'Image'
                ),
        ));
*/
/*        $this->add(array(
                //'type' => 'Zend\Form\Element\Hidden',
                'name' => 'points',
                'options' => array(
                    'label' => 'Points',
                    'class' => 'input-mini'
                ),
                'attributes' =>  array(
                    'class' => 'input-mini'
                ),
        ));
*/

    }
}
