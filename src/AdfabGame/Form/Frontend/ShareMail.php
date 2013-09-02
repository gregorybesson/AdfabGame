<?php
namespace AdfabGame\Form\Frontend;

use Zend\Form\Form;
use Zend\Form\Element;
use ZfcBase\Form\ProvidesEventsForm;
use Zend\I18n\Translator\Translator;
use Zend\ServiceManager\ServiceManager;

class ShareMail extends ProvidesEventsForm
{

    protected $serviceManager;

    public function __construct ($name = null, ServiceManager $sm, Translator $translator)
    {
        parent::__construct($name);

        $this->setServiceManager($sm);
        $entityManager = $this->getServiceManager()->get('doctrine.entitymanager.orm_default');

        $this->add(array(
            'name' => 'email1',
            'options' => array(
                'label' => $translator->translate('Adresse email 1', 'adfabgame')
            ),
            'attributes' => array(
                'type' => 'email',
                'placeholder' => $translator->translate('Adresse email 1', 'adfabgame'),
                'class' => 'large-input',
                'autocomplete' => 'off'
            )
        ));

        $this->add(array(
            'name' => 'email2',
            'options' => array(
                'label' => $translator->translate('Adresse email 2', 'adfabgame')
            ),
            'attributes' => array(
                'type' => 'email',
                'placeholder' => $translator->translate('Adresse email 2', 'adfabgame'),
                'class' => 'large-input',
                'autocomplete' => 'off'
            )
        ));

        $this->add(array(
            'name' => 'email3',
            'options' => array(
                'label' => $translator->translate('Adresse email 3', 'adfabgame')
            ),
            'attributes' => array(
                'type' => 'email',
                'placeholder' => $translator->translate('Adresse email 3', 'adfabgame'),
                'class' => 'large-input',
                'autocomplete' => 'off'
            )
        ));

        $submitElement = new Element\Button('submit');
        $submitElement->setLabel($translator->translate('Envoyer', 'adfabgame'))
            ->setAttributes(array(
            'type' => 'submit',
            'class'=> 'btn btn-warning'
        ));

        $this->add($submitElement, array(
            'priority' => - 100
        ));
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
