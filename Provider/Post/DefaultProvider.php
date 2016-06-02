<?php

namespace Rz\NewsPageBundle\Provider\Post;

use Sonata\AdminBundle\Form\FormMapper;
use Sonata\CoreBundle\Validator\ErrorElement;
use Sonata\MediaBundle\Model\GalleryInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Sonata\NewsBundle\Model\PostInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Rz\NewsBundle\Provider\Post\BaseProvider;

class DefaultProvider extends BaseProvider implements NewsPageProviderInterface
{
    protected $templates = [];
    protected $defaultTemplate;
    protected $isControllerEnabled;
    protected $isNew;

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
            ->tab('tab.rz_news_settings')
                ->with('rz_news_settings', array('class' => 'col-md-12',))
                    ->add('settings', 'sonata_type_immutable_array', array('keys' => $this->getFormSettingsKeys($formMapper, $object), 'required'=>false, 'label'=>false, 'attr'=>array('class'=>'rz-immutable-container')))
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

        if($this->getIsControllerEnabled() || $object->isNew() || !$object->getSetting('template')) {

            $settings[] = array('template',
                                'choice',
                                array('choices'=>$this->getTemplates(),
                                      'attr'=>array('class'=>'span4'),
                                      'help_block' => $this->getTranslator()->trans('help.provider_block_template_new', array(), 'SonataNewsBundle'),
                                      'preferred_choices' => $this->getPreferedChoice(),
                                ));
        } else {
            $settings[] = array('template', 'text', array('help_block' => $this->getTranslator()->trans('help.provider_block_template', array(), 'SonataNewsBundle'),'attr'=>array('readonly'=>'readonly')));
        }

        return $settings;
    }

    /**
     * @return array
     */
    public function getTemplates()
    {
        return $this->templates;
    }

    /**
     * @param array $templates
     */
    public function setTemplates($templates)
    {
        $this->templates = $templates;
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
     * @return mixed
     */
    public function getDefaultTemplate()
    {
        return $this->defaultTemplate;
    }

    /**
     * @param mixed $defaultTemplate
     */
    public function setDefaultTemplate($defaultTemplate)
    {
        $this->defaultTemplate = $defaultTemplate;
    }

    public function getPreferedChoice() {
        $template = $this->getDefaultTemplate() ?: null;
        if($template) {
            return array($template);
        }
        return [];
    }

    public function load(PostInterface $object) {}
}
