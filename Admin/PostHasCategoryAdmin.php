<?php


namespace Rz\NewsPageBundle\Admin;

use Rz\NewsBundle\Admin\PostHasCategoryAdmin as Admin;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\AdminBundle\Route\RouteCollection;

class PostHasCategoryAdmin extends Admin
{
    protected $parentAssociationMapping = 'post';
    protected $siteManager;
    protected $transformer;

    /**
     * {@inheritdoc}
     */
    protected function configureDatagridFilters(DatagridMapper $filter)
    {
        $filter
            ->add('post.site', null, array('show_filter' => false))
            ->add('post.title')
            ->add('post.publicationDateStart', 'doctrine_orm_datetime_range', array('field_type' => 'sonata_type_datetime_range_picker'))
            ->add('category', null, array('show_filter' => false,));
    }

    /**
     * {@inheritdoc}
     */
    public function configureActionButtons($action, $object = null)
    {
        $list = parent::configureActionButtons($action, $object);

        if (in_array($action, array('tree', 'show', 'edit', 'delete', 'list', 'batch'))) {
            $list['create'] = array(
                'template' => 'RzNewsPageBundle:PostHasCategoryAdmin:Button\create_button.html.twig',
            );
        }
        return $list;
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

    /**
     * @return mixed
     */
    public function getTransformer()
    {
        return $this->transformer;
    }

    /**
     * @param mixed $transformer
     */
    public function setTransformer($transformer)
    {
        $this->transformer = $transformer;
    }



    public function getPersistentParameters()
    {
        $parameters = parent::getPersistentParameters();

        if($this->hasParentFieldDescription()) {
            return $parameters;
        }

        $site = $this->getSite($this->getRequest()->get('site', null));

        $parameters['site'] = $site ? $site->getId() : null;

        return $parameters;
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

    /**
     * {@inheritdoc}
     */
    public function preUpdate($object)
    {
        parent::preUpdate($object);
        if (interface_exists('Sonata\PageBundle\Model\PageInterface')) {
            $post = $object->getPost();
            $this->getTransformer()->update($post);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function postPersist($object)
    {
        parent::postPersist($object);
        if (interface_exists('Sonata\PageBundle\Model\PageInterface')) {
            $post = $object->getPost();
            $this->getTransformer()->create($post);
        }
    }
}
