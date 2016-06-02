<?php

namespace Rz\NewsPageBundle\Block;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Templating\EngineInterface;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Admin\AdminInterface;
use Sonata\BlockBundle\Block\BaseBlockService;
use Sonata\BlockBundle\Block\BlockContextInterface;
use Sonata\BlockBundle\Model\BlockInterface;
use Sonata\CoreBundle\Model\ManagerInterface;
use Sonata\CoreBundle\Model\Metadata;
use Sonata\NewsBundle\Admin\PostAdmin;
use Sonata\NewsBundle\Model\PostInterface;
use Sonata\PageBundle\CmsManager\CmsManagerSelectorInterface;
use Sonata\PageBundle\Site\SiteSelectorInterface;

/**
 * PageExtension.
 *
 * @author     Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class PostBlockService extends BaseBlockService
{

    /**
     * @var SiteSelectorInterface
     */
    protected $siteSelector;

    /**
     * @var CmsManagerSelectorInterface
     */
    protected $cmsManagerSelector;

    protected $postAdmin;

    /**
     * @var ManagerInterface
     */
    protected $postManager;

    /**
     * @param string             $name
     * @param EngineInterface    $templating
     * @param ContainerInterface $container
     * @param ManagerInterface   $mediaManager
     */
    public function __construct($name,
                                EngineInterface $templating,
                                ManagerInterface $postManager,
                                AdminInterface $postAdmin,
                                SiteSelectorInterface $siteSelector,
                                CmsManagerSelectorInterface $cmsManagerSelector)
    {
        parent::__construct($name, $templating);

        $this->postManager = $postManager;
        $this->postAdmin    = $postAdmin;
        $this->siteSelector       = $siteSelector;
        $this->cmsManagerSelector = $cmsManagerSelector;
    }

    public function getPostAdmin()
    {
        return $this->postAdmin;
    }


    /**
     * {@inheritdoc}
     */
    public function configureSettings(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'postId'      => false,
            'template'    => 'RzNewsPageBundle:Block:block_post_default.html.twig',
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function buildEditForm(FormMapper $formMapper, BlockInterface $block)
    {

        if (!$block->getSetting('postId') instanceof PostInterface) {
            $this->load($block);
        }

        $formMapper
            ->add('settings', 'sonata_type_immutable_array', array(
                'keys' => array(
                    array($this->getPostBuilder($formMapper), null, array()),
                ),
                'translation_domain' => 'SonataNewsBundle',
                'attr'=>array('class'=>'rz-immutable-container')
            ));
    }

    /**
     * @param FormMapper $formMapper
     *
     * @return FormBuilder
     */
    protected function getPostBuilder(FormMapper $formMapper)
    {
        // simulate an association ...
        $fieldDescription = $this->getPostAdmin()->getModelManager()->getNewFieldDescriptionInstance($this->postAdmin->getClass(), 'post', array(
            'translation_domain' => 'SonataNewsBundle',
        ));
        $fieldDescription->setAssociationAdmin($this->getPostAdmin());
        $fieldDescription->setAdmin($formMapper->getAdmin());
        $fieldDescription->setOption('edit', 'list');
        $fieldDescription->setAssociationMapping(array(
            'fieldName' => 'postId',
            'type'      => ClassMetadataInfo::MANY_TO_ONE,
        ));

        return $formMapper->create('postId', 'sonata_type_model_list', array(
            'sonata_field_description' => $fieldDescription,
            'class'                    => $this->getPostAdmin()->getClass(),
            'model_manager'            => $this->getPostAdmin()->getModelManager(),
            'label'                    => 'form.label_post',
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockMetadata($code = null)
    {
        return new Metadata($this->getName(), (!is_null($code) ? $code : $this->getName()), false, 'SonataNewsBundle', array(
            'class' => 'fa fa-file-text-o',
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function load(BlockInterface $block)
    {
        $post = $block->getSetting('postId', null);

        if (is_int($post)) {
            $post = $this->postManager->findOneBy(array('id' => $post));
        }

        $block->setSetting('postId', $post);
    }

    /**
     * {@inheritdoc}
     */
    public function prePersist(BlockInterface $block)
    {
        $block->setSetting('postId', is_object($block->getSetting('postId')) ? $block->getSetting('postId')->getId() : null);
    }

    /**
     * {@inheritdoc}
     */
    public function preUpdate(BlockInterface $block)
    {
        $block->setSetting('postId', is_object($block->getSetting('postId')) ? $block->getSetting('postId')->getId() : null);
    }

    /**
     * {@inheritdoc}
     */
    public function execute(BlockContextInterface $blockContext, Response $response = null)
    {

        $cmsManager = $this->cmsManagerSelector->retrieve();
        $page = $cmsManager->getCurrentPage();

        return $this->renderResponse($blockContext->getTemplate(), array(
            'block_context'  => $blockContext,
            'block'          => $blockContext->getBlock(),
            'page'           => $page,
        ), $response);
    }
}
