<?php

namespace Rz\NewsPageBundle\Controller;

use Rz\NewsBundle\Controller\PostHasCategoryAdminController as Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class PostHasCategoryAdminController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function listAction(Request $request = null)
    {
        if (false === $this->admin->isGranted('LIST')) {
            throw new AccessDeniedException();
        }

        if ($listMode = $request->get('_list_mode', 'list')) {
            $this->admin->setListMode($listMode);
        }

        $datagrid = $this->admin->getDatagrid();
        $filters = $request->get('filter');
        $postHasCategoryManager = $this->get('rz.news.manager.post_has_category');
        $categories = $postHasCategoryManager->getUniqueCategories();

        if (!$filters || !array_key_exists('category', $filters)) {
            $currentCategory = current($categories);
        } else {
            $category = $this->container->get('sonata.classification.manager.category')->findOneBy(array('id' => (int) $filters['category']['value']));
            $currentCategory = array('id'=>$category->getId(), 'name'=>$category->getName());
        }

        if ($request->get('category')) {
            $category = $this->container->get('sonata.classification.manager.category')->findOneBy(array('id' => (int) $request->get('category')));
            if($category) {
                $currentCategory = array('id'=>$category->getId(), 'name'=>$category->getName());
            }
        }

        $datagrid->setValue('category', null, $currentCategory['id']);

        $formView = $datagrid->getForm()->createView();

        // set the theme for the current Admin Form
        $this->get('twig')->getExtension('form')->renderer->setTheme($formView, $this->admin->getFilterTheme());

        return $this->render($this->admin->getTemplate('list'), array(
            'action'                => 'list',
            'form'                  => $formView,
            'datagrid'              => $datagrid,
            'categories'            => $categories,
            'current_category'      => $currentCategory,
            'csrf_token'            => $this->getCsrfToken('sonata.batch'),
        ));
    }
}
