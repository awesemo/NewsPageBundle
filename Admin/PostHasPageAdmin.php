<?php


namespace Rz\NewsPageBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Route\RouteCollection;

class PostHasPageAdmin extends Admin
{

    protected $parentAssociationMapping = 'post';
    protected $siteManager;

    protected $datagridValues = array(
        'isCanonical' => array(
            'value' => true
        )
    );

    /**
     * @param \Sonata\AdminBundle\Form\FormMapper $formMapper
     *
     * @return void
     */
    protected function configureFormFields(FormMapper $formMapper)
    {

        $formMapper->add('page', 'sonata_type_model_list', array('btn_delete' => false, 'btn_add' => false), array(
            'link_parameters' => array('context' => 'news', 'hide_context' => true, 'mode' => 'list'),
        ));


        $formMapper
            ->add('enabled', null, array('required' => false))
            ->add('position', 'hidden')
        ;
    }

    /**
     * @param  \Sonata\AdminBundle\Datagrid\ListMapper $listMapper
     * @return void
     */
    protected function configureListFields(ListMapper $listMapper)
    {
        $isCanonical = $this->getPersistentParameter('isCanonical');

        $listMapper
            ->add('page', null, array('associated_property' => 'url'))
            ->add('post', null, array('associated_property' => 'title', 'footable'=>array('attr'=>array('data-breakpoints'=>array('all')))))
            ->add('enabled', null, array('footable'=>array('attr'=>array('data-breakpoints'=>array('xs', 'sm'))), 'editable' => false))
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureDatagridFilters(DatagridMapper $filter)
    {
        $filter
            ->add('post.site', null, array('show_filter' => false,))
            ->add('post.collection', null, array('show_filter' => false,))
            ->add('isCanonical', null, array('show_filter' => false,))
            ->add('post')
            ->add('page')
            ->add('enabled');
    }

    /**
     * {@inheritdoc}
     */
    protected function configureRoutes(RouteCollection $collection)
    {
        $collection->clearExcept(array('list', 'edit', 'create', 'show'));
    }

    /**
     * @return mixed
     */
    public function getSiteManager()
    {
        return $this->siteManager;
    }

    /**
     * @param mixed $siteManager
     */
    public function setSiteManager($siteManager)
    {
        $this->siteManager = $siteManager;
    }

    protected function getSite($siteId = null) {
        $sites = $this->getSiteManager()->findBy(array());
        $currentSite = null;
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

        return $currentSite;
    }

    public function getPersistentParameters()
    {
        $parameters = [];
        $site = $this->getSite($this->getRequest()->get('site', null));
        $parameters['site'] = $site ? $site->getId() : null;
        $parameters['isCanonical'] = $this->getRequest()->get('isCanonical', null);

        return $parameters;
    }
}
