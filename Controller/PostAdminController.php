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

    /**
     * Edit action.
     *
     * @param int|string|null $id
     *
     * @return Response|RedirectResponse
     *
     * @throws NotFoundHttpException If the object does not exist
     * @throws AccessDeniedException If access is not granted
     */
    public function editAction($id = null)
    {
        $request = $this->getRequest();
        // the key used to lookup the template
        $templateKey = 'edit';

        $id     = $request->get($this->admin->getIdParameter());
        $object = $this->admin->getObject($id);

        if (!$object) {
            throw $this->createNotFoundException(sprintf('unable to find the object with id : %s', $id));
        }

        $this->admin->checkAccess('edit', $object);

        $preResponse = $this->preEdit($request, $object);
        if ($preResponse !== null) {
            return $preResponse;
        }

        $this->admin->setSubject($object);

        /** @var $form Form */
        $form = $this->admin->getForm();
        $form->setData($object);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {

            //TODO: remove this check for 4.0
            if (method_exists($this->admin, 'preValidate')) {
                $this->admin->preValidate($object);
            }
            $isFormValid = $form->isValid();

            // persist if the form was valid and if in preview mode the preview was approved
            if ($isFormValid && (!$this->isInPreviewMode() || $this->isPreviewApproved())) {
                try {
                    $object = $this->admin->update($object);

                    if ($this->isXmlHttpRequest()) {
                        return $this->renderJson(array(
                            'result'     => 'ok',
                            'objectId'   => $this->admin->getNormalizedIdentifier($object),
                            'objectName' => $this->escapeHtml($this->admin->toString($object)),
                        ), 200, array());
                    }

                    $this->addFlash(
                        'sonata_flash_success',
                        $this->admin->trans(
                            'flash_edit_success',
                            array('%name%' => $this->escapeHtml($this->admin->toString($object))),
                            'SonataAdminBundle'
                        )
                    );

                    // redirect to edit mode
                    return $this->redirectTo($object);
                } catch (ModelManagerException $e) {
                    $this->handleModelManagerException($e);

                    $isFormValid = false;
                } catch (LockException $e) {
                    $this->addFlash('sonata_flash_error', $this->admin->trans('flash_lock_error', array(
                        '%name%'       => $this->escapeHtml($this->admin->toString($object)),
                        '%link_start%' => '<a href="'.$this->admin->generateObjectUrl('edit', $object).'">',
                        '%link_end%'   => '</a>',
                    ), 'SonataAdminBundle'));
                }
            }

            // show an error message if the form failed validation
            if (!$isFormValid) {
                if (!$this->isXmlHttpRequest()) {
                    $this->addFlash(
                        'sonata_flash_error',
                        $this->admin->trans(
                            'flash_edit_error',
                            array('%name%' => $this->escapeHtml($this->admin->toString($object))),
                            'SonataAdminBundle'
                        )
                    );
                }
            } elseif ($this->isPreviewRequested()) {
                // enable the preview template if the form was valid and preview was requested
                $templateKey = 'preview';
                $this->admin->getShow();
            }
        }

        $view = $form->createView();

        // set the theme for the current Admin Form
        $this->get('twig')->getExtension('form')->renderer->setTheme($view, $this->admin->getFormTheme());

        return $this->render($this->admin->getTemplate($templateKey), array(
            'action' => 'edit',
            'form'   => $view,
            'object' => $object,
        ), null);
    }
}
