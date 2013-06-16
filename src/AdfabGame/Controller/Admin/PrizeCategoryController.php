<?php

namespace AdfabGame\Controller\Admin;

use AdfabGame\Entity\PrizeCategory;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use AdfabGame\Options\ModuleOptions;

class PrizeCategoryController extends AbstractActionController
{
    protected $options;

    /**
     * @var prizeCategoryService
     */
    protected $prizeCategoryService;

    public function listAction()
    {
        $service = $this->getPrizeCategoryService();
        $categories = $service->getPrizeCategoryMapper()->findAll();

        if (is_array($categories)) {
            $paginator = new \Zend\Paginator\Paginator(new \Zend\Paginator\Adapter\ArrayAdapter($categories));
        } else {
            $paginator = $categories;
        }

        $paginator->setItemCountPerPage(10);
        $paginator->setCurrentPageNumber($this->getEvent()->getRouteMatch()->getParam('p'));

        return array('categories' => $paginator);
    }

    public function addAction()
    {
        $form = $this->getServiceLocator()->get('adfabgame_prizecategory_form');
        $form->get('submit')->setLabel('Créer');
        $request = $this->getRequest();

        $category = new PrizeCategory();
        $form->bind($category);

        if ($request->isPost()) {
            $data = array_merge(
                    $request->getPost()->toArray(),
                    $request->getFiles()->toArray()
            );
            $category = $this->getPrizeCategoryService()->create($data, $category, 'adfabgame_prizecategory_form');
            if ($category) {
                $this->flashMessenger()->setNamespace('adfabgame')->addMessage('la catégorie a été créée');

                return $this->redirect()->toRoute('zfcadmin/adfabgame/prize-category-list');
            }
        }

        $viewModel = new ViewModel();
        $viewModel->setTemplate('adfab-game/admin/prize-category');

        return $viewModel->setVariables(array('form' => $form));
    }

    public function editAction()
    {
        $prizeCategoryId = $this->getEvent()->getRouteMatch()->getParam('prizeCategoryId');
        $category = $this->getPrizeCategoryService()->getPrizeCategoryMapper()->findById($prizeCategoryId);

        $form = $this->getServiceLocator()->get('adfabgame_prizecategory_form');
        $form->get('submit')->setLabel('Mettre à jour');

        $request = $this->getRequest();

        $form->bind($category);

        if ($request->isPost()) {
            $data = array_merge(
                    $request->getPost()->toArray(),
                    $request->getFiles()->toArray()
            );
            $category = $this->getPrizeCategoryService()->edit($data, $category, 'adfabgame_prizecategory_form');
            if ($category) {
                $this->flashMessenger()->setNamespace('adfabgame')->addMessage('La catégorie a été mise à jour');

                return $this->redirect()->toRoute('zfcadmin/adfabgame/prize-category-list');
            }
        }

        $viewModel = new ViewModel();
        $viewModel->setTemplate('adfab-game/admin/prize-category');

        return $viewModel->setVariables(array('form' => $form));
    }

    public function setOptions(ModuleOptions $options)
    {
        $this->options = $options;

        return $this;
    }

    public function getOptions()
    {
        if (!$this->options instanceof ModuleOptions) {
            $this->setOptions($this->getServiceLocator()->get('adfabgame_module_options'));
        }

        return $this->options;
    }

    public function getPrizeCategoryService()
    {
        if (!$this->prizeCategoryService) {
            $this->prizeCategoryService = $this->getServiceLocator()->get('adfabgame_prizecategory_service');
        }

        return $this->prizeCategoryService;
    }

    public function setPrizeCategoryService(PrizeCategoryService $prizeCategoryService)
    {
        $this->prizeCategoryService = $prizeCategoryService;

        return $this;
    }
}
