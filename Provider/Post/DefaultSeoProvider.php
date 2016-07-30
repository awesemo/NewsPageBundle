<?php

namespace Rz\NewsPageBundle\Provider\Post;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\CoreBundle\Validator\ErrorElement;
use Sonata\MediaBundle\Model\GalleryInterface;
use Sonata\NewsBundle\Model\PostInterface;
use Rz\NewsBundle\Provider\Post\BaseProvider;

class DefaultSeoProvider extends BaseProvider
{
    protected $metatagChoices = [];
    protected $mediaAdmin;
    protected $mediaManager;
    protected $isNew;
    protected $translator;

    /**
     * @return array
     */
    public function getMetatagChoices()
    {
        return $this->metatagChoices;
    }

    /**
     * @param array $metatagChoices
     */
    public function setMetatagChoices($metatagChoices)
    {
        $this->metatagChoices = $metatagChoices;
    }

    /**
     * {@inheritdoc}
     */
    public function buildEditForm(FormMapper $formMapper, $object = null)
    {
        $this->buildCreateForm($formMapper, $object);
    }

    /**
     * {@inheritdoc}
     */
    public function buildCreateForm(FormMapper $formMapper, $object = null)
    {
        $formMapper
            ->tab('tab.rz_news_seo_settings')
                ->with('rz_news_seo_settings', array('class' => 'col-md-12', ))
                    ->add('seoSettings', 'sonata_type_immutable_array', array('keys' => $this->getFormSettingsKeys($formMapper, $object), 'required'=>false, 'label'=>false, 'attr'=>array('class'=>'rz-immutable-container')))
                ->end()
            ->end();
    }

    /**
     * @param FormMapper $formMapper
     * @param null $object
     * @return array
     */
    public function getFormSettingsKeys(FormMapper $formMapper, $object = null)
    {
        $settings = [];

        $settings[] = array('seoTitle', 'text', array('required' => false));
        $settings[] = array('seoMetaKeyword', 'textarea', array('required' => false, 'attr'=>array('rows'=>5)));
        $settings[] = array('seoMetaDescription', 'textarea', array('required' => false, 'attr'=>array('rows'=>5)));
        $settings[] = array('ogTitle', 'text', array('required' => false, 'attr'=>array('class'=>'span8')));
        $settings[] = array('ogType', 'choice', array('choices'=>$this->getMetatagChoices(), 'attr'=>array('class'=>'span4')));
        $settings[] = array('ogDescription', 'textarea', array('required' => false, 'attr'=>array('class'=>'span8', 'rows'=>5)));


        if (interface_exists('Sonata\MediaBundle\Model\MediaInterface')) {
            $settings[] = array($this->getMediaBuilder($formMapper), null, array());
        }

        return $settings;
    }

    protected function getMediaBuilder(FormMapper $formMapper)
    {
        $mediaAdmin = clone $this->mediaAdmin;
        // simulate an association media...
        $fieldDescription =  $mediaAdmin->getModelManager()->getNewFieldDescriptionInstance($mediaAdmin->getClass(), 'media');
        $fieldDescription->setAssociationAdmin($mediaAdmin);
        $fieldDescription->setAdmin($formMapper->getAdmin());
        $fieldDescription->setOption('edit', 'list');
        $fieldDescription->setOptions(array('link_parameters' => array('context' => 'news', 'hide_context' => true)));
        $fieldDescription->setAssociationMapping(array(
            'fieldName' => 'media',
            'type'      => \Doctrine\ORM\Mapping\ClassMetadataInfo::MANY_TO_ONE
        ));

        return $formMapper->create('ogImage', 'sonata_type_model_list', array(
            'sonata_field_description' => $fieldDescription,
            'class'                    => $mediaAdmin->getClass(),
            'model_manager'            => $mediaAdmin->getModelManager()),
            array('link_parameters' => array('context' => 'news', 'hide_context' => true))
        );
    }

    public function load(PostInterface $object)
    {
        if (interface_exists('Sonata\MediaBundle\Model\MediaInterface')) {
            //load media
            $media = $object->getSeoSetting('ogImage', null);
            if (is_int($media)) {
                $media = $this->mediaManager->findOneBy(array('id' => $media));
            }
            $object->setSeoSetting('ogImage', $media);
        }
    }

    public function getOptions()
    {
    }

    /**
     * @return mixed
     */
    public function getMediaAdmin()
    {
        return $this->mediaAdmin;
    }

    /**
     * @param mixed $mediaAdmin
     */
    public function setMediaAdmin($mediaAdmin)
    {
        $this->mediaAdmin = $mediaAdmin;
    }

    /**
     * @return mixed
     */
    public function getMediaManager()
    {
        return $this->mediaManager;
    }

    /**
     * @param mixed $mediaManager
     */
    public function setMediaManager($mediaManager)
    {
        $this->mediaManager = $mediaManager;
    }

    /**
     * {@inheritdoc}
     */
    public function postPersist(PostInterface $post)
    {
        $post->setSeoSetting('ogImage', is_object($post->getSeoSetting('ogImage')) ? $post->getSeoSetting('ogImage')->getId() : null);
        parent::postPersist($post);
    }

    /**
     * {@inheritdoc}
     */
    public function postUpdate(PostInterface $post)
    {
        $post->setSeoSetting('ogImage', is_object($post->getSeoSetting('ogImage')) ? $post->getSeoSetting('ogImage')->getId() : null);
        parent::postUpdate($post);
    }

    /**
     * @return mixed
     */
    public function getTranslator()
    {
        return $this->translator;
    }

    /**
     * @param mixed $translator
     */
    public function setTranslator($translator)
    {
        $this->translator = $translator;
    }
}
