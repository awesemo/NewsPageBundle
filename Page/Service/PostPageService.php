<?php

namespace Rz\NewsPageBundle\Page\Service;

use Sonata\PageBundle\Model\PageInterface;
use Sonata\PageBundle\Page\TemplateManagerInterface;
use Sonata\SeoBundle\Seo\SeoPageInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Cmf\Component\Routing\ChainedRouterInterface;
use Rz\NewsPageBundle\Page\Service\PostCanonicalPageService as BasePageService;

/**
 * Default page service to render a page template.
 *
 * Note: this service is backward-compatible and functions like the old page renderer class.
 *
 * @author Olivier Paradis <paradis.olivier@gmail.com>
 */
class PostPageService extends BasePageService
{
    /**
     * @var TemplateManagerInterface
     */
    protected $templateManager;

    /**
     * @var SeoPageInterface
     */
    protected $seoPage;

    protected $router;

    /**
     * Constructor.
     *
     * @param string                   $name            Page service name
     * @param TemplateManagerInterface $templateManager Template manager
     * @param SeoPageInterface         $seoPage         SEO page object
     */
    public function __construct($name, TemplateManagerInterface $templateManager, SeoPageInterface $seoPage = null, ChainedRouterInterface $router)
    {
        $this->name            = $name;
        $this->templateManager = $templateManager;
        $this->seoPage         = $seoPage;
        $this->router          = $router;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(PageInterface $page, Request $request, array $parameters = array(), Response $response = null)
    {
        $this->updateSeoPage($page);

        $response = $this->templateManager->renderResponse($page->getTemplateCode(), $parameters, $response);

        return $response;
    }

    /**
     * Updates the SEO page values for given page instance.
     *
     * @param PageInterface $page
     */
    protected function updateSeoPage(PageInterface $page)
    {
        if (!$this->seoPage) {
            return;
        }

        if ($page->getTitle()) {
            $this->seoPage->setTitle($page->getTitle() ?: $page->getName());
        }

        if ($page->getMetaDescription()) {
            $this->seoPage->addMeta('name', 'description', $page->getMetaDescription());
        }

        if ($page->getMetaKeyword()) {
            $this->seoPage->addMeta('name', 'keywords', $page->getMetaKeyword());
        }

        $this->seoPage->addMeta('property', 'og:type', 'article');
        $this->seoPage->addHtmlAttributes('prefix', 'og: http://ogp.me/ns#');

        if($page->getCanonicalPage()) {
            $this->seoPage->setLinkCanonical($this->router->generate($page->getCanonicalPage(), array(), ChainedRouterInterface::ABSOLUTE_URL));
        }
    }
}
