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
use Sonata\PageBundle\CmsManager\CmsManagerSelectorInterface;
use Sonata\PageBundle\Site\SiteSelectorInterface;
use Sonata\ClassificationBundle\Admin\CategoryAdmin;
use Sonata\ClassificationBundle\Model\CategoryInterface;

/**
 * PageExtension.
 *
 * @author     Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class PostListByCategoryBlockService extends BaseBlockService
{

    /**
     * @var SiteSelectorInterface
     */
    protected $siteSelector;

    /**
     * @var CmsManagerSelectorInterface
     */
    protected $cmsManagerSelector;

    protected $categoryAdmin;

    /**
     * @var ManagerInterface
     */
    protected $categoryManager;

    protected $postHasPageManager;

    /**
     * @param string             $name
     * @param EngineInterface    $templating
     * @param ContainerInterface $container
     * @param ManagerInterface   $mediaManager
     */
    public function __construct($name,
                                EngineInterface $templating,
                                SiteSelectorInterface $siteSelector,
                                CmsManagerSelectorInterface $cmsManagerSelector)
    {
        parent::__construct($name, $templating);
        $this->siteSelector       = $siteSelector;
        $this->cmsManagerSelector = $cmsManagerSelector;
    }

    /**
     * @return AdminInterface
     */
    public function getCategoryAdmin()
    {
        return $this->categoryAdmin;
    }

    /**
     * @param AdminInterface $categoryAdmin
     */
    public function setCategoryAdmin($categoryAdmin)
    {
        $this->categoryAdmin = $categoryAdmin;
    }

    /**
     * @return ManagerInterface
     */
    public function getCategoryManager()
    {
        return $this->categoryManager;
    }

    /**
     * @param ManagerInterface $categoryManager
     */
    public function setCategoryManager($categoryManager)
    {
        $this->categoryManager = $categoryManager;
    }

    /**
     * @return mixed
     */
    public function getPostHasPageManager()
    {
        return $this->postHasPageManager;
    }

    /**
     * @param mixed $postHasPageManager
     */
    public function setPostHasPageManager($postHasPageManager)
    {
        $this->postHasPageManager = $postHasPageManager;
    }

    /**
     * {@inheritdoc}
     */
    public function configureSettings(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'categoryId'  => null,
            'template'    => 'RzCategoryPageBundle:Block:block_category_post_list.html.twig',
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function buildEditForm(FormMapper $formMapper, BlockInterface $block)
    {
        if (!$block->getSetting('categoryId') instanceof CategoryInterface) {
            $this->load($block);
        }

        $formMapper
            ->add('settings', 'sonata_type_immutable_array', array(
                'keys' => array(
                    array($this->getCategoryBuilder($formMapper), null, array()),
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
    protected function getCategoryBuilder(FormMapper $formMapper)
    {
        // simulate an association ...
        $fieldDescription = $this->getCategoryAdmin()->getModelManager()->getNewFieldDescriptionInstance($this->categoryAdmin->getClass(), 'category', array(
            'translation_domain' => 'SonataClassificationBundle',
        ));
        $fieldDescription->setAssociationAdmin($this->getCategoryAdmin());
        $fieldDescription->setAdmin($formMapper->getAdmin());
        $fieldDescription->setOption('edit', 'list');
        $fieldDescription->setAssociationMapping(array(
            'fieldName' => 'categoryId',
            'type'      => ClassMetadataInfo::MANY_TO_ONE,
        ));

        return $formMapper->create('categoryId', 'sonata_type_model_list', array(
            'sonata_field_description' => $fieldDescription,
            'class'                    => $this->getCategoryAdmin()->getClass(),
            'model_manager'            => $this->getCategoryAdmin()->getModelManager(),
            'label'                    => 'form.label_name',
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
        $category = $block->getSetting('categoryId', null);

        if (is_int($category)) {
            $category = $this->categoryManager->findOneBy(array('id' => $category));
        }

        $block->setSetting('categoryId', $category);
    }

    /**
     * {@inheritdoc}
     */
    public function prePersist(BlockInterface $block)
    {
        $block->setSetting('categoryId', is_object($block->getSetting('categoryId')) ? $block->getSetting('categoryId')->getId() : null);
    }

    /**
     * {@inheritdoc}
     */
    public function preUpdate(BlockInterface $block)
    {
        $block->setSetting('categoryId', is_object($block->getSetting('categoryId')) ? $block->getSetting('categoryId')->getId() : null);
    }

    /**
     * {@inheritdoc}
     */
    public function execute(BlockContextInterface $blockContext, Response $response = null)
    {
        $cmsManager = $this->cmsManagerSelector->retrieve();
        $page = $cmsManager->getCurrentPage();

        $category = $blockContext->getBlock()->getSetting('categoryId');

        $pager = $this->getPostHasPageManager()->getPostsByCategoryPager(array('category'=>$category), 1);

        return $this->renderResponse($blockContext->getTemplate(), array(
            'block_context'  => $blockContext,
            'block'          => $blockContext->getBlock(),
            'page'           => $page,
            'pager'          => $pager
        ), $response);
    }
}
