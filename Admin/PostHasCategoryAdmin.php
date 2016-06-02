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

    /**
     * @param \Sonata\AdminBundle\Form\FormMapper $formMapper
     *
     * @return void
     */
    protected function configureFormFields(FormMapper $formMapper)
    {
        if (interface_exists('Sonata\ClassificationBundle\Model\CategoryInterface')) {
            $formMapper->add('category', 'sonata_type_model_list', array('btn_delete' => false), array());
        }

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
        $listMapper
            ->add('post', null, array('associated_property' => 'title'))
            ->add('position', null, array('footable'=>array('attr'=>array('data-breakpoints'=>array('xs', 'sm'))), 'editable' => true))
            ->add('post.publicationDateStart', null, array('footable'=>array('attr'=>array('data-breakpoints'=>array('xs', 'sm')))))
        ;
    }

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
    protected function configureRoutes(RouteCollection $collection)
    {
        $collection->clearExcept(array('list', 'edit', 'create', 'show'));
    }
}
