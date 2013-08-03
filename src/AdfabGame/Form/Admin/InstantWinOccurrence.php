<?php

namespace AdfabGame\Form\Admin;

use Zend\Form\Form;
use Zend\Form\Element;
use ZfcBase\Form\ProvidesEventsForm;
use Zend\I18n\Translator\Translator;
use DoctrineModule\Stdlib\Hydrator\DoctrineObject as DoctrineHydrator;
use Zend\ServiceManager\ServiceManager;

class InstantWinOccurrence extends ProvidesEventsForm
{
    public function __construct($name = null, ServiceManager $serviceManager, Translator $translator)
    {
        parent::__construct($name);

        $entityManager = $serviceManager->get('doctrine.entitymanager.orm_default');

        // The form will hydrate an object of type "QuizQuestion"
        // This is the secret for working with collections with Doctrine
        // (+ add'Collection'() and remove'Collection'() and "cascade" in corresponding Entity
        // https://github.com/doctrine/DoctrineModule/blob/master/docs/hydrator.md
        /*$hydrator = new DoctrineHydrator($entityManager, 'AdfabGame\Entity\InstantWinOccurrence');
        $hydrator->addStrategy('prize', new \AdfabCore\Stdlib\Hydrator\Strategy\ObjectStrategy());
        $this->setHydrator($hydrator);*/

        $this->setAttribute('method', 'post');
        //$this->setAttribute('class','form-horizontal');

        $this->setServiceManager($serviceManager);

        $this->add(array(
            'name' => 'instant_win_id',
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
            'name' => 'occurrence_date',
            'options' => array(
                'label' => $translator->translate('Occurrence Date', 'adfabgame'),
            ),
            'attributes' => array(
                'type' => 'text',
                'id' => 'occurrence_date'
            ),
        ));

        $this->add(array(
            'type' => 'Zend\Form\Element\Select',
            'name' => 'active',
            'options' => array(
            //'empty_option' => $translator->translate('Is the answer correct ?', 'adfabgame'),
                'value_options' => array(
                    '0' => $translator->translate('No', 'adfabgame'),
                    '1' => $translator->translate('Yes', 'adfabgame'),
                ),
                'label' => $translator->translate('Active', 'adfabgame'),
            ),
        ));

        $prizes = $this->getPrizes();
        $this->add(array(
        	'type' => 'Zend\Form\Element\Select',
        	'name' => 'prize',
        	'options' => array(
        		'empty_option' => $translator->translate('Ce jeu n\'a pas lot associé', 'adfabgame'),
        		'value_options' => $prizes,
        		'label' => $translator->translate('Lots', 'adfabgame')
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

    /**
     *
     * @return array
     */
    public function getPrizes ()
    {
    	$prizes = array();
    	$prizeService = $this->getServiceManager()->get('adfabgame_prize_service');
    	$results = $prizeService->getPrizeMapper()->findAll();

    	foreach ($results as $result) {
    		$prizes[$result->getId()] = $result->getTitle();
    	}

    	return $prizes;
    }

    /**
     * Retrieve service manager instance
     *
     * @return ServiceManager
     */
    public function getServiceManager ()
    {
    	return $this->serviceManager;
    }

    /**
     * Set service manager instance
     *
     * @param  ServiceManager $serviceManager
     * @return User
     */
    public function setServiceManager (ServiceManager $serviceManager)
    {
    	$this->serviceManager = $serviceManager;

    	return $this;
    }
}
