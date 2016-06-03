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
        $listMapper
            ->add('page', null, array('associated_property' => 'url'))
            ->add('post', null, array('associated_property' => 'title', 'footable'=>array('attr'=>array('data-breakpoints'=>array('all')))))
            ->add('enabled', null, array('footable'=>array('attr'=>array('data-breakpoints'=>array('xs', 'sm'))), 'editable' => false))
//            ->add('_action', 'actions', array(
//                'actions' => array(
//                    'Show' => array('template' => 'SonataAdminBundle:CRUD:list__action_show.html.twig'),
//                    'Edit' => array('template' => 'SonataAdminBundle:CRUD:list__action_edit.html.twig'),
//                    'Delete' => array('template' => 'SonataAdminBundle:CRUD:list__action_delete.html.twig')),
//                'footable'=>array('attr'=>array('data_hide'=>'phone,tablet')),
//            ))
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureDatagridFilters(DatagridMapper $filter)
    {
        $filter
            ->add('post')
            ->add('page')
            ->add('isCanonical', null, array('show_filter' => false,))
            ->add('enabled');
    }

    /**
     * {@inheritdoc}
     */
    protected function configureRoutes(RouteCollection $collection)
    {
        $collection->clearExcept(array('list', 'edit', 'create', 'show'));
    }
}
