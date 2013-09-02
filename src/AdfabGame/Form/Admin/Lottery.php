<?php

namespace AdfabGame\Form\Admin;

use Zend\Form\Form;
use AdfabCore\Stdlib\Hydrator\DoctrineObject as DoctrineHydrator;
use Zend\Form\Element;
use Zend\I18n\Translator\Translator;
use Zend\ServiceManager\ServiceManager;

class Lottery extends Game
{
    public function __construct($name = null, ServiceManager $sm, Translator $translator)
    {
        $this->setServiceManager($sm);
        $entityManager = $sm->get('doctrine.entitymanager.orm_default');

        // having to fix a Doctrine-module bug :( https://github.com/doctrine/DoctrineModule/issues/180
        $hydrator = new DoctrineHydrator($entityManager, 'AdfabGame\Entity\Lottery');
        $hydrator->addStrategy('partner', new \AdfabCore\Stdlib\Hydrator\Strategy\ObjectStrategy());
        $this->setHydrator($hydrator);

        parent::__construct($name, $sm, $translator);

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
                'type' => 'Zend\Form\Element\DateTime',
                'name' => 'drawDate',
                'options' => array(
                    'label' => $translator->translate('Date du tirage au sort', 'adfabgame'),
                    'format'=>'d/m/Y',
                ),
                'attributes' => array(
                    'type' => 'text',
                    'class'=> 'datepicker'
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

    }
}
