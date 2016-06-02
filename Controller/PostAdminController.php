<?php

namespace Rz\NewsPageBundle\Controller;

use Rz\NewsBundle\Controller\PostAdminController as CRUDController;
use Sonata\AdminBundle\Datagrid\ProxyQueryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;

class PostAdminController extends CRUDController
{
    /**
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listAction(Request $request = null)
    {
        #site TODO: should have check if pageBunlde is not available
        $siteManager = $this->get('sonata.page.manager.site');
        $sites = $siteManager->findBy(array());
        $currentSite = null;
        $siteId = $request->get('site');
        foreach ($sites as $site) {
            if ($siteId && $site->getId() == $siteId) {
                $currentSite = $site;
            } elseif (!$siteId && $site->getIsDefault()) {
                $currentSite = $site;
            }
        }
        if (!$currentSite && count($sites) == 1) {
            $currentSite = $sites[0];
        }

        $this->admin->checkAccess('list');

        $preResponse = $this->preList($request);
        if ($preResponse !== null) {
            return $preResponse;
        }

        if ($listMode = $request->get('_list_mode', 'mosaic')) {
            $this->admin->setListMode($listMode);
        }

        $datagrid = $this->admin->getDatagrid();


        if ($this->admin->getPersistentParameter('site')) {
            $site = $siteManager->findOneBy(array('id'=>$this->admin->getPersistentParameter('site')));
            $datagrid->setValue('site', null, $site->getId());
        } else {
            $datagrid->setValue('site', null, $currentSite->getId());
        }

        $collectiontManager = $this->get('sonata.classification.manager.collection');
        $slugify = $this->get($this->container->getParameter('rz.news.slugify_service'));

        $contextManager = $this->get('sonata.classification.manager.context');
        $defaultContext = $this->container->getParameter('rz.news.post.default_context');
        $context = $contextManager->findOneBy(array('id'=>$slugify->slugify($defaultContext)));

        if(!$context && !$context instanceof \Sonata\ClassificationBundle\Model\ContextInterface) {
            $context = $contextManager->generateDefaultContext($defaultContext);
        }

        $currentCollection = null;
        $defaultCollection = $this->container->getParameter('rz.news.post.default_collection');


        if ($collection = $request->get('collection')) {
            $currentCollection = $collectiontManager->findOneBy(array('slug'=>$slugify->slugify($collection), 'context'=>$context));
        } else {
            $currentCollection = $collectiontManager->findOneBy(array('slug'=>$slugify->slugify($defaultCollection), 'context'=>$context));
        }

        $collections = $collectiontManager->findBy(array('context'=>$context));

        if(!$currentCollection &&
            !$currentCollection instanceof \Sonata\ClassificationBundle\Model\CollectionInterface &&
            count($collections) === 0) {
            $currentCollection = $collectiontManager->generateDefaultCollection($context, $defaultCollection);
            $collections = $collectiontManager->findBy(array('context'=>$context));
        }

        if(count($collections)>0) {

            if (!$currentCollection) {
                list($currentCollection) = $collections;
            }

            if ($this->admin->getPersistentParameter('collection')) {
                $collection = $collectiontManager->findOneBy(array('context'=>$context, 'slug'=>$this->admin->getPersistentParameter('collection')));
                if($collection && $collection instanceof \Sonata\ClassificationBundle\Model\CollectionInterface) {
                    $datagrid->setValue('collection', null, $collection->getId());
                } else {
                    throw $this->createNotFoundException($this->get('translator')->trans('page_not_found', array(), 'SonataAdminBundle'));
                }
            } else {
                $datagrid->setValue('collection', null, $currentCollection->getId());
            }
        }

        $formView = $datagrid->getForm()->createView();

        // set the theme for the current Admin Form
        $this->get('twig')->getExtension('form')->renderer->setTheme($formView, $this->admin->getFilterTheme());

        return $this->render($this->admin->getTemplate('list'), array(
            'action'              => 'list',
            'current_collection'  => $currentCollection,
            'collections'         => $collections,
            'sites'               => $sites,
            'currentSite'         => $currentSite,
            'form'                => $formView,
            'datagrid'            => $datagrid,
            'csrf_token'          => $this->getCsrfToken('sonata.batch'),
        ), null, $request);
    }
}
