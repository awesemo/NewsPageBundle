<?php

namespace Rz\NewsPageBundle\Twig\Extension;

use Sonata\BlockBundle\Templating\Helper\BlockHelper;
use Sonata\PageBundle\Model\PageInterface;
use Sonata\PageBundle\Model\SnapshotPageProxy;
use Symfony\Component\HttpFoundation\Response;
use Sonata\CoreBundle\Model\BaseEntityManager;
use Sonata\NewsBundle\Model\PostInterface;
use Sonata\ClassificationBundle\Model\CategoryInterface;

/**
 * PageExtension.
 *
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class NewsPageExtension extends \Twig_Extension implements \Twig_Extension_InitRuntimeInterface
{
    /**
     * @var CmsManagerSelectorInterface
     */
    private $postHasPageManager;

    /**
     * @var array
     */
    private $resources;

    /**
     * @var \Twig_Environment
     */
    private $environment;

    /**
     * @var HttpKernelExtension
     */
    private $httpKernelExtension;

    /**
     * Constructor.
     *
     * @param BaseEntityManager $postHasPageManager
     */
    public function __construct(BaseEntityManager $postHasPageManager) {
        $this->postHasPageManager  = $postHasPageManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('rz_news_page_canonical', array($this, 'pageCanonical')),
            new \Twig_SimpleFunction('rz_news_page_by_category', array($this, 'pageByCategory')),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function initRuntime(\Twig_Environment $environment)
    {
        $this->environment = $environment;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'rz_news_page';
    }

    /**
     * Returns the URL for an ajax request for given block.
     *
     * @param PageBlockInterface $block      Block service
     * @param array              $parameters Provide absolute or relative url ?
     * @param bool               $absolute
     *
     * @return string
     */
    public function pageCanonical(PostInterface $post, $isCanonical = true)
    {
        $postHasPage = $this->postHasPageManager->findOneByPageAndIsCanonical(array('post'=>$post, 'is_canonical'=>$isCanonical));
        return $postHasPage->getPage();
    }

    /**
     * @param string $template
     * @param array  $parameters
     *
     * @return string
     */
    private function pageByCategory($template, array $parameters = array())
    {
        if (!isset($this->resources[$template])) {
            $this->resources[$template] = $this->environment->loadTemplate($template);
        }

        return $this->resources[$template]->render($parameters);
    }
}
