<?php

namespace AdfabGame\Form\Admin;

use Zend\Form\Form;
use Zend\Form\Element;
use ZfcBase\Form\ProvidesEventsForm;
use Zend\I18n\Translator\Translator;
use DoctrineModule\Stdlib\Hydrator\DoctrineObject as DoctrineHydrator;
use Zend\ServiceManager\ServiceManager;

class TreasureHuntStep extends ProvidesEventsForm
{
    public function __construct($name = null, ServiceManager $serviceManager, Translator $translator)
    {
        parent::__construct($name);

        $entityManager = $serviceManager->get('doctrine.entitymanager.orm_default');

        $this->setAttribute('method', 'post');

        $this->setServiceManager($serviceManager);

        $this->add(array(
            'name' => 'treasurehunt_id',
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
        		'type' => 'Zend\Form\Element\Textarea',
        		'name' => 'area',
        		'options' => array(
        				'label' => $translator->translate('Area', 'adfabgame'),
        				'label_attributes' => array(
        					'class' => 'control-label',
        				),
        		),
        		'attributes' => array(
        				'required' => false,
        				'cols' => '40',
        				'rows' => '8',
        				'id' => 'area',
        		),
        ));
        
        $this->add(array(
            'type' => 'Zend\Form\Element\Select',
            'name' => 'timer',
            'options' => array(
                'value_options' => array(
                    '0' => $translator->translate('Non', 'adfabgame'),
                    '1' => $translator->translate('Oui', 'adfabgame')
                ),
                'label' => $translator->translate('Timer', 'adfabgame')
            )
        ));
        
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
        		'name' => 'position',
        		'options' => array(
        				'label' => $translator->translate('Position', 'adfabgame'),
        				'label_attributes' => array(
        					'class' => 'control-label',
        				),
        		),
        ));
        
        $this->add(array(
        		'name' => 'url',
        		'options' => array(
        			'label' => $translator->translate('Url', 'adfabgame'),
        			'label_attributes' => array(
        				'class' => 'control-label',
       				),
        		),
        		'attributes' => array(
		            'id' => 'url',
        		),
        ));
        
        $this->add(array(
       		'name' => 'domain',
       		'options' => array(
       			'label' => $translator->translate('Domain', 'adfabgame'),
       			'label_attributes' => array(
       				'class' => 'control-label',
       			),
       		),
        	'attributes' => array(
        		'id' => 'domain',
       		),
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
