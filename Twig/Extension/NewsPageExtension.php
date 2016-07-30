<?php

namespace Rz\NewsPageBundle\Twig\Extension;

use Sonata\BlockBundle\Templating\Helper\BlockHelper;
use Sonata\PageBundle\Model\PageInterface;
use Sonata\PageBundle\Model\SnapshotPageProxy;
use Symfony\Component\HttpFoundation\Response;
use Sonata\CoreBundle\Model\BaseEntityManager;
use Sonata\NewsBundle\Model\PostInterface;
use Sonata\CoreBundle\Model\ManagerInterface;


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

    private $categoryHasPageManager;

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
    public function __construct(ManagerInterface $postHasPageManager, ManagerInterface$categoryHasPageManager) {
        $this->postHasPageManager  = $postHasPageManager;
        $this->categoryHasPageManager = $categoryHasPageManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('rz_news_page_page_by_post', array($this, 'pageByPost')),
            new \Twig_SimpleFunction('rz_news_page_page_by_category', array($this, 'pageByCategory')),
            new \Twig_SimpleFunction('rz_news_page_page_by_category_post', array($this, 'pageByCategoryAndPost')),
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
    public function pageByPost($post)
    {
        $postHasPage = $this->postHasPageManager->fetchCanonicalPage($post);

        if(!$postHasPage) {
            return null;
        }
        return $postHasPage->getPage();
    }

    /**
     * @param string $template
     * @param array  $parameters
     *
     * @return string
     */
    public function pageByCategoryAndPost($category, $post)
    {
        $postHasPage = $this->postHasPageManager->findOneByPostAndCategory(array('category'=>$category, 'post'=>$post, 'is_canonical'=>false));

        if(!$postHasPage) {
            return null;
        }

        return $postHasPage->getPage();
    }

    /**
     * @param string $template
     * @param array  $parameters
     *
     * @return string
     */
    public function pageByCategory($category)
    {
        $categoryHasPage = $this->categoryHasPageManager->findOneByCategory(array('category'=>$category));

        if(!$categoryHasPage) {
            return null;
        }
        return $categoryHasPage->getPage();
    }
}
