<?php

namespace AdfabGame\Form\Admin;

use Zend\Form\Form;
use AdfabCore\Stdlib\Hydrator\DoctrineObject as DoctrineHydrator;
use Zend\Form\Element;
use Zend\I18n\Translator\Translator;
use Zend\ServiceManager\ServiceManager;

class TreasureHunt extends Game
{
    public function __construct($name = null, ServiceManager $sm, Translator $translator)
    {
        $this->setServiceManager($sm);
        $entityManager = $sm->get('doctrine.entitymanager.orm_default');

        // having to fix a Doctrine-module bug :( https://github.com/doctrine/DoctrineModule/issues/180
        $hydrator = new DoctrineHydrator($entityManager, 'AdfabGame\Entity\TreasureHunt');
        $hydrator->addStrategy('partner', new \AdfabCore\Stdlib\Hydrator\Strategy\ObjectStrategy());
        $this->setHydrator($hydrator);

        parent::__construct($name, $sm, $translator);

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
        	'type' => 'Zend\Form\Element\Select',
        	'name' => 'playerType',
       		'attributes' =>  array(
   				'id' => 'playerType',
       			'options' => array(
       				'all' => $translator->translate('All', 'adfabgame'),
 					'prospect' => $translator->translate('Prospect', 'adfabgame'),
        			'customer' => $translator->translate('Customer', 'adfabgame'),
   				),
       		),
        	'options' => array(
        		'empty_option' => $translator->translate('Quel type de joueur peut participer', 'adfabgame'),
       			'label' => $translator->translate('Type de joueur pouvant participer', 'adfabgame'),
       		),
        ));
    }
}
