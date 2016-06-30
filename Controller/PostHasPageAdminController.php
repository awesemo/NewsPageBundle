<?php

namespace Rz\NewsPageBundle\Controller;

use Sonata\AdminBundle\Controller\CRUDController as Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class PostHasPageAdminController extends Controller
{
    public function listAction(Request $request = null)
    {
        if (false === $this->admin->isGranted('LIST')) {
            throw new AccessDeniedException();
        }

        if ($listMode = $request->get('_list_mode', 'list')) {
            $this->admin->setListMode($listMode);
        }

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

        $datagrid = $this->admin->getDatagrid();
        $filters = $request->get('filter');
        $isCanonical = null;

        if ($filters && array_key_exists('isCanonical', $filters)) {
            $isCanonical = $filters['isCanonical']['value'];
        }

        $canonicalValues =  array(true,false);

        if ($request->get('isCanonical') && in_array($request->get('isCanonical'),$canonicalValues)) {
            $isCanonical = $request->get('isCanonical');
        }

        $datagrid->setValue('isCanonical', null, $isCanonical);
        $datagrid->setValue('post__site', null, $currentSite->getId());

        $collectiontManager = $this->get('sonata.classification.manager.collection');
        $slugify = $this->get($this->container->getParameter('rz.news.slugify_service'));
        $defaultCollection = $this->container->getParameter('rz.news.post.default_collection');

        if ($collection = $request->get('collection')) {
            $collection = $collectiontManager->findOneBy(array('slug'=>$slugify->slugify($collection)));
        } else {
            $collection = $collectiontManager->findOneBy(array('slug'=>$slugify->slugify($defaultCollection)));
        }

        if($collection) {
            $datagrid->setValue('post__collection', null, $collection->getId());
        }

        $formView = $datagrid->getForm()->createView();

        // set the theme for the current Admin Form
        $this->get('twig')->getExtension('form')->renderer->setTheme($formView, $this->admin->getFilterTheme());

        return $this->render($this->admin->getTemplate('list'), array(
            'action'                => 'list',
            'form'                  => $formView,
            'datagrid'              => $datagrid,
            'sites'                 => $sites,
            'currentSite'           => $currentSite,
            'csrf_token'            => $this->getCsrfToken('sonata.batch'),
        ));
    }
}
