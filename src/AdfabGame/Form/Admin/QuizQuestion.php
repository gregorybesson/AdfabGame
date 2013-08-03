<?php

namespace AdfabGame\Form\Admin;

use Zend\Form\Form;
use Zend\Form\Element;
use ZfcBase\Form\ProvidesEventsForm;
use Zend\I18n\Translator\Translator;
use DoctrineModule\Stdlib\Hydrator\DoctrineObject as DoctrineHydrator;
use Zend\ServiceManager\ServiceManager;

class QuizQuestion extends ProvidesEventsForm
{
    public function __construct($name = null, ServiceManager $serviceManager, Translator $translator)
    {
        parent::__construct($name);

        $entityManager = $serviceManager->get('doctrine.entitymanager.orm_default');

        // The form will hydrate an object of type "QuizQuestion"
        // This is the secret for working with collections with Doctrine
        // (+ add'Collection'() and remove'Collection'() and "cascade" in corresponding Entity
        // https://github.com/doctrine/DoctrineModule/blob/master/docs/hydrator.md
        $this->setHydrator(new DoctrineHydrator($entityManager, 'AdfabGame\Entity\QuizQuestion'));

        $this->setAttribute('method', 'post');
        $this->setAttribute('enctype','multipart/form-data');
        $this->setAttribute('class','form-horizontal');

        $this->add(array(
            'name' => 'quiz_id',
            'type'  => 'Zend\Form\Element\Hidden',
            'attributes' => array(
                'value' => 0,
            ),
        ));

        $this->add(array(
            'name' => 'id',
            'type'  => 'Zend\Form\Element\Hidden',
            'attributes' => array(
                'value' => 0,
            ),
        ));

        $this->add(array(
                'type' => 'Zend\Form\Element\Textarea',
                'name' => 'question',
                'options' => array(
                    'label' => $translator->translate('Question', 'adfabgame'),
                    'label_attributes' => array(
                        'class' => 'control-label',
                    ),
                ),
                'attributes' => array(
                    'required' => false,
                    'cols' => '10',
                    'rows' => '2',
                    'id' => 'question',
                ),
        ));

        $this->add(array(
            'type' => 'Zend\Form\Element\Textarea',
            'name' => 'hint',
            'options' => array(
                'label' => $translator->translate('Indice', 'adfabgame'),
                'label_attributes' => array(
                    'class' => 'control-label',
                ),
            ),
            'attributes' => array(
                'required' => false,
                'cols' => '10',
                'rows' => '2',
                'id' => 'question',
            ),
        ));

        $this->add(array(
            'type' => 'Zend\Form\Element\Checkbox',
            'name' => 'prediction',
            'options' => array(
                'label' => 'Question pronostic',
                'label_attributes' => array(
                    'class' => 'control-label',
                ),
            ),
        ));

        $this->add(array(
            'name' => 'timer',
            'type'  => 'Zend\Form\Element\Hidden',
            'attributes' => array(
                'value' => 0,
            ),
        ));

        /*$this->add(array(
            'name' => 'timer',
            'type' => 'Zend\Form\Element\Radio',
            'options' => array(
                'label' => $translator->translate('Inclure un chrono', 'adfabgame'),
                'label_attributes' => array(
                    'class' => 'control-label',
                ),
                'value_options' => array(
                    '0' => $translator->translate('Non', 'adfabgame'),
                    '1' => $translator->translate('Oui', 'adfabgame'),
                ),
            ),
        ));*/

        $this->add(array(
            'name' => 'timer_duration',
            'options' => array(
                'label' => $translator->translate('Durée du chrono', 'adfabgame'),
                'label_attributes' => array(
                    'class' => 'control-label',
                ),
            ),
        ));

        $this->add(array(
            'name' => 'type',
            'type' => 'Zend\Form\Element\Radio',
            'options' => array(
                'label' => $translator->translate('Type', 'adfabgame'),
                'label_attributes' => array(
                    'class' => 'control-label',
                ),
                'value_options' => array(
                    '0' => $translator->translate('Single response', 'adfabgame'),
                    '1' => $translator->translate('Multiple choice', 'adfabgame'),
                    '2' => $translator->translate('Input field', 'adfabgame'),
                ),
            ),
        ));

        $this->add(array(
            'type'  => 'Zend\Form\Element\Hidden',
            'name' => 'weight',
            'options' => array(
                'label' => $translator->translate('Weight', 'adfabgame'),
                'label_attributes' => array(
                    'class' => 'control-label',
                ),
            ),
        ));

        $this->add(array(
            'name' => 'position',
            'options' => array(
                'label' => $translator->translate('Position', 'adfabgame'),
                'label_attributes' => array(
                    'class' => 'control-label',
                ),
            ),
        ));

        $this->add(array(
            'name' => 'video',
            'options' => array(
                'label' => $translator->translate('Video URL', 'adfabgame'),
                'label_attributes' => array(
                    'class' => 'control-label',
                ),
            ),
        ));

        // Adding an empty upload field to be able to correctly handle this on the service side.
        $this->add(array(
                'name' => 'upload_image',
                'attributes' => array(
                    'type'  => 'file',
                ),
                'options' => array(
                    'label' => $translator->translate('Image', 'adfabgame'),
                    'label_attributes' => array(
                        'class' => 'control-label',
                    ),
                ),
        ));
        $this->add(array(
                'name' => 'image',
                'type'  => 'Zend\Form\Element\Hidden',
                'attributes' => array(
                        'value' => '',
                ),
        ));

        $quizAnswerFieldset = new QuizAnswerFieldset(null,$serviceManager,$translator);
        $this->add(array(
            'type'    => 'Zend\Form\Element\Collection',
            'name'    => 'answers',
            'options' => array(
                'id'    => 'answers',
                'label' => $translator->translate('List of answers', 'adfabgame'),
                'count' => 0,
                'should_create_template' => true,
                'allow_add' => true,
                'allow_remove' => true,
                'target_element' => $quizAnswerFieldset
            )
        ));

        $submitElement = new Element\Button('submit');
        $submitElement
        ->setLabel($translator->translate('Create', 'adfabgame'))
        ->setAttributes(array(
            'type'  => 'submit',
            'class' => 'btn btn-primary',
        ));

        $this->add($submitElement, array(
            'priority' => -100,
        ));

    }
}
