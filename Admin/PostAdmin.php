<?php

namespace Rz\NewsPageBundle\Admin;

use Rz\NewsBundle\Admin\PostAdmin as Admin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Knp\Menu\ItemInterface as MenuItemInterface;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\CoreBundle\Model\ManagerInterface;
use Sonata\CoreBundle\Validator\ErrorElement;

class PostAdmin extends Admin
{
    protected $siteManager;
    protected $transformer;
    protected $seoProvider;
    protected $isControllerEnabled;
    protected $pageTemplates = [];

    /**
     * {@inheritdoc}
     */
    protected function configureFormFields(FormMapper $formMapper)
    {

        if($this->hasProvider()) {
            $formMapper
                ->tab('tab.rz_news')
                    ->with('group_post', array('class' => 'col-md-8'))->end()
                    ->with('group_status', array('class' => 'col-md-4'))->end()
                    ->with('group_content', array('class' => 'col-md-12'))->end()
                ->end()
                ->tab('tab.rz_news_settings')
                    ->with('rz_news_settings', array('class' => 'col-md-12'))->end()
                ->end()
                ->tab('tab.rz_news_tags')
                    ->with('group_classification', array('class' => 'col-md-12'))->end()
                ->end()
                ->tab('tab.rz_news_category')
                    ->with('rz_news_category', array('class' => 'col-md-12'))->end()
                ->end();
        } else {
            $formMapper
                ->tab('tab.rz_news')
                    ->with('group_post', array('class' => 'col-md-8'))->end()
                    ->with('group_status', array('class' => 'col-md-4'))->end()
                    ->with('group_content', array('class' => 'col-md-12'))->end()
                ->end()
                ->tab('tab.rz_news_tags')
                    ->with('group_classification', array('class' => 'col-md-12'))->end()
                ->end()
                ->tab('tab.rz_news_category')
                    ->with('rz_news_category', array('class' => 'col-md-12'))->end()
                ->end();
        }

        if (interface_exists('Sonata\PageBundle\Model\PageInterface')) {

            $formMapper->tab('tab.rz_news_seo_settings')
                            ->with('rz_news_seo_settings', array('class' => 'col-md-12'))->end()
                       ->end();
        }

        if($this->getPostHasMediaEnabled()) {
            $formMapper
                ->tab('tab.rz_news_media')
                    ->with('rz_news_media', array('class' => 'col-md-12'))->end()
                ->end();
        }


        if($this->getRelatedArticleEnabled()) {
            $formMapper
                ->tab('tab.rz_news_related_articles')
                    ->with('rz_news_related_articles', array('class' => 'col-md-12'))->end()
                ->end();
        }


        if($this->getSuggestedArticleEnabled()) {
            $formMapper
                ->tab('tab.rz_news_suggested_articles')
                    ->with('rz_news_suggested_articles', array('class' => 'col-md-12'))->end()
                ->end();
        }

        $formMapper
            ->tab('tab.rz_news')
                ->with('group_post', array(
                    'class' => 'col-md-8',
                ))
                ->add('title')
                ->add('image', 'sonata_type_model_list', array('required' => false), array(
                    'link_parameters' => array(
                        'context'      => $this->getDefaultContext(),
                        'hide_context' => true,
                    ),
                ))
                ->add('abstract', null, array('attr' => array('rows' => 5)))
                ->end()
                ->with('group_status', array('class' => 'col-md-4',))
                    ->add('enabled', null, array('required' => false))
                    ->add('publicationDateStart', 'sonata_type_datetime_picker', array('dp_side_by_side' => true))
                    ->add('publicationDateEnd',   'sonata_type_datetime_picker', array('dp_side_by_side' => true))
                ->end()

                ->with('group_content', array('class' => 'col-md-12',))
                    ->add('content', 'sonata_formatter_type', array(
                        'event_dispatcher'          => $formMapper->getFormBuilder()->getEventDispatcher(),
                        'format_field'              => 'contentFormatter',
                        'source_field'              => 'rawContent',
                        'source_field_options'      => array(
                            'horizontal_input_wrapper_class' => $this->getConfigurationPool()->getOption('form_type') == 'horizontal' ? 'col-lg-12' : '',
                            'attr'                           => array('class' => $this->getConfigurationPool()->getOption('form_type') == 'horizontal' ? 'span10 col-sm-10 col-md-10' : '', 'rows' => 20),
                        ),
                        'ckeditor_context'     => $this->getDefaultContext(),
                        'target_field'         => 'content',
                        'listener'             => true))
                ->end()
            ->end();

         ##############################
        # TAGS
        ##############################

        $formMapper
            ->tab('tab.rz_news_tags')
                ->with('group_classification', array('class' => 'col-md-8'))
                    ->add('tags', 'sonata_type_model', array(
                        'property' => 'name',
                        'multiple' => 'true',
                        'required' => false,
                        'expanded' => true,
                        'query'    => $this->getTagManager()->geTagQueryForDatagrid(array($this->getDefaultContext()))),
                        array('link_parameters' => array(
                              'context'      => $this->getDefaultContext(),
                              'hide_context' => true)))
                ->end()
            ->end();

        ##############################
        # CATEGORY
        ##############################

        $formMapper
            ->tab('tab.rz_news_category')
                ->with('rz_news_category', array('class' => 'col-md-12'))
                    ->add('postHasCategory', 'sonata_type_collection', array(
                        'cascade_validation' => true,
                        'required' => false),
                         array(
                                'edit'              => 'inline',
                                'inline'            => 'table',
                                'sortable'          => 'position',
                                'link_parameters'   => array('context' => $this->getDefaultContext()),
                                'admin_code'        => 'rz.news.admin.post_has_category',
                            ))
                ->end()
            ->end();

        ##############################
        # MEDIA
        ##############################

        if($this->getPostHasMediaEnabled()) {
            $formMapper
                ->tab('tab.rz_news_media')
                    ->with('rz_news_media', array('class' => 'col-md-12'))
                        ->add('postHasMedia', 'sonata_type_collection', array(
                            'cascade_validation' => true,
                            'required'           => false),
                            array(
                                    'edit'            => 'inline',
                                    'inline'          => 'standard',
                                    'sortable'        => 'position',
                                    'link_parameters' => $this->getPostHasMediaSettings(),
                                    'admin_code'      => 'rz.news.admin.post_has_media'))
                    ->end()
                ->end();
        }

        ##############################
        # RELATED ARTICLE
        ##############################

        if($this->getRelatedArticleEnabled()) {
            $formMapper
                ->tab('tab.rz_news_related_articles')
                    ->with('rz_news_related_articles', array('class' => 'col-md-12'))
                        ->add('relatedArticles', 'sonata_type_collection', array(
                            'cascade_validation' => true,
                            'required' => false),
                            array(
                                'edit'              => 'inline',
                                'inline'            => 'table',
                                'sortable'          => 'position',
                                'link_parameters'   => array('context' => $this->getDefaultContext()),
                                'admin_code'        => 'rz.news.admin.related_articles'))
                    ->end()
                ->end();
        }

        ##############################
        # SUGGESTED ARTICLE
        ##############################

        if($this->getSuggestedArticleEnabled()) {
            $formMapper
                ->tab('tab.rz_news_suggested_articles')
                    ->with('rz_news_suggested_articles', array('class' => 'col-md-12'))
                        ->add('suggestedArticles', 'sonata_type_collection', array(
                            'cascade_validation' => true,
                            'required' => false),
                            array(
                                    'edit'              => 'inline',
                                    'inline'            => 'table',
                                    'sortable'          => 'position',
                                    'link_parameters'   => $this->getSuggetedArticleSettings(),
                                    'admin_code'        => 'rz.news.admin.suggested_articles'))
                    ->end()
                ->end();
        }

        $instance = $this->getSubject();

        #ADD page template if news does not use controller
        if ($this->isGranted('ROLE_ALLOWED_TO_SWITCH')) {
            $formMapper->tab('tab.rz_news')->with('group_status');
            if ($instance && $instance->getId()) {
                $formMapper->add('author', 'sonata_type_model_list');
            }
            $formMapper->end()->end();
        }


        if($this->hasProvider()) {
            if ($instance && $instance->getId()) {
                $this->getProvider()->load($instance);
                $this->getProvider()->buildEditForm($formMapper, $instance);
            } else {
                $this->getProvider()->buildCreateForm($formMapper, $instance);
            }

            //ADD page template if news does not use controller
            $formMapper->tab('tab.rz_news_settings')->with('rz_news_settings');
            if (interface_exists('Sonata\PageBundle\Model\BlockInteractorInterface') &&
                $formMapper->has('settings') &&
                !$this->getIsControllerEnabled()) {

                $settingsField = $formMapper->get('settings');

                if ($instance && $instance->getId() && $instance->getSetting('pageTemplateCode')) {
                    $settingsField->add('pageTemplateCode',
                        'text',
                        array('help_block' => $this->getTranslator()->trans('help.provider_page_template_code', array(), 'SonataNewsBundle'),
                              'required'   => true,
                              'attr'       => array('readonly' => 'readonly')
                        ));
                } else {
                    $settingsField->add('pageTemplateCode',
                        'choice',
                        array('choices'    => $this->getPageTemplates(),
                              'help_block' => $this->getTranslator()->trans('help.provider_page_template_code_new', array(), 'SonataNewsBundle'),
                              'required'   => true
                        ));
                }
            }
            $formMapper->end()->end();
        }

        //SEO PROVIDER
        $seoProvider = $this->getSeoProvider();
        if($seoProvider && interface_exists('Sonata\PageBundle\Model\PageInterface')) {
            if ($instance && $instance->getId()) {
                $seoProvider->load($instance);
                $seoProvider->buildEditForm($formMapper, $instance);
            } else {
                $seoProvider->buildCreateForm($formMapper, $instance);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        parent::configureDatagridFilters($datagridMapper);

        $datagridMapper
            ->add('site', null, array('show_filter' => false));
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
    public function setSiteManager(ManagerInterface $siteManager)
    {
        $this->siteManager = $siteManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getPersistentParameters()
    {
        $site = $this->getSite();

        $parameters = array(
            'collection'      => $this->getDefaultCollection(),
            'site'            => $site ? $site : '',
            'hide_collection' => $this->hasRequest() ? (int) $this->getRequest()->get('hide_collection', 0) : 0);

        if ($this->getSubject()) {
            $parameters['collection'] = $this->getSubject()->getCollection() ? $this->getSubject()->getCollection()->getSlug() : $this->getDefaultCollection();
            $parameters['site']       = $this->getSubject()->getSite() ? $this->getSubject()->getSite()->getId() : '';
            return $parameters;
        }

        if ($this->hasRequest()) {
            $parameters['collection'] = $this->getRequest()->get('collection');
            $parameters['site'] = $this->getRequest()->get('site');
            return $parameters;
        }

        return $parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function getNewInstance()
    {
        $instance = parent::getNewInstance();

        if ($site = $this->getSite()) {
            $instance->setSite($site);
        }

        return $instance;
    }

    /**
     * {@inheritdoc}
     */
    public function preUpdate($object)
    {
        parent::preUpdate($object);

        if (interface_exists('Sonata\PageBundle\Model\PageInterface')) {
            $object->setPostHasPage($object->getPostHasPage());
            $this->getTransformer()->update($object);
        }
    }

    /**
     * @return SiteInterface|bool
     *
     * @throws \RuntimeException
     */
    public function getSite()
    {
        if (!$this->hasRequest()) {
            return false;
        }

        $siteId = null;

        if ($this->getRequest()->getMethod() == 'POST') {
            $values = $this->getRequest()->get($this->getUniqid());
            $siteId = isset($values['site']) ? $values['site'] : null;
        }

        $siteId = (null !== $siteId) ? $siteId : $this->getRequest()->get('site');

        if ($siteId) {
            $site = $this->siteManager->findOneBy(array('id' => $siteId));

            if (!$site) {
                throw new \RuntimeException('Unable to find the site with id='.$this->getRequest()->get('site'));
            }

            return $site;
        } else {
            return $this->siteManager->findOneBy(array('host'=>'localhost'));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function postPersist($object)
    {
        parent::postPersist($object);
        if (interface_exists('Sonata\PageBundle\Model\PageInterface')) {
            $object->setPostHasPage($object->getPostHasPage());
            $this->getTransformer()->create($object);
        }
    }

    /**
     * @return mixed
     */
    public function getSeoProvider()
    {
        return $this->seoProvider;
    }

    /**
     * @param mixed $seoProvider
     */
    public function setSeoProvider($seoProvider)
    {
        $this->seoProvider = $seoProvider;
    }

    /**
     * @return mixed
     */
    public function hasSeoProvider($interface = null)
    {
        if(!$interface) {
            return isset($this->seoProvider);
        }

        if($this->seoProvider instanceof $interface) {
            return true;
        }
        return false;
    }

    /**
     * @return mixed
     */
    public function getIsControllerEnabled()
    {
        return $this->isControllerEnabled;
    }

    /**
     * @param mixed $isControllerEnabled
     */
    public function setIsControllerEnabled($isControllerEnabled)
    {
        $this->isControllerEnabled = $isControllerEnabled;
    }

    /**
     * @return array
     */
    public function getPageTemplates()
    {
        return $this->pageTemplates;
    }

    /**
     * @param array $pageTemplates
     */
    public function setPageTemplates($pageTemplates)
    {
        $this->pageTemplates = $pageTemplates;
    }

    /**
     * {@inheritdoc}
     */
    public function validate(ErrorElement $errorElement, $object)
    {
        parent::validate($errorElement, $object);
        if($this->hasSeoProvider()) {
            $this->getSeoProvider()->validate($errorElement, $object);
        }
    }
}
