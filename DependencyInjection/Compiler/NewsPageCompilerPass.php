<?php

namespace Rz\NewsPageBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerInterface;

class NewsPageCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->getParameter('rz.news_page.enable_controller')) {
            $this->attachNewsPage($container);
        }

        $this->attachSiteSettings($container);
        $this->attachSeoSettings($container);
    }

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     */
    public function attachSiteSettings(ContainerBuilder $container)
    {
        $definition = $container->getDefinition('sonata.news.admin.post');
        $definition->addMethodCall('setSiteManager', array(new Reference('sonata.page.manager.site')));
    }

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     */
    public function attachSeoSettings(ContainerBuilder $container)
    {
        //SEO Provider
        $defaultProvider = $container->getParameter('rz.news_page.default_seo_provider');
        $seoProvider = $container->getDefinition($defaultProvider);
        $seoProvider->addMethodCall('setPostManager', array(new Reference('sonata.news.manager.post')));
        $seoProvider->addMethodCall('setMediaAdmin', array(new Reference('sonata.media.admin.media')));
        $seoProvider->addMethodCall('setMediaManager', array(new Reference('sonata.media.manager.media')));
        $seoProvider->addMethodCall('setMetatagChoices', array($container->getParameter('rz_seo.metatags')));

        $definition = $container->getDefinition('sonata.news.admin.post');
        $definition->addMethodCall('setSeoProvider', array(new Reference($defaultProvider)));
    }

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     */
    public function attachNewsPage(ContainerBuilder $container)
    {
        $transformer = $container->getDefinition('rz.news_page.transformer');
        #set slugify service
        $serviceId = $container->getParameter('rz.news_page.slugify_service');
        $transformer->addMethodCall('setSlugify', array(new Reference($serviceId)));
        #set default values
        $transformer->addMethodCall('setDefaultNewsPageSlug', array($container->getParameter('rz.news_page.parent_slug')));
        $transformer->addMethodCall('setPostBlockService', array($container->getParameter('rz.news_page.post_block_service')));
        $pageServices = array_merge($container->getParameter('rz.news_page.page.services'), $container->getParameter('rz.category_page.page.services'));
        $transformer->addMethodCall('setPageServices', array($pageServices));

        if (interface_exists('Rz\CategoryPageBundle\Model\CategoryHasPageInterface')) {
            $transformer->addMethodCall('setCategoryPostListService', array($container->getParameter('rz.category_page.block.catgory_post_list.service')));
            $transformer->addMethodCall('setCategoryHasPageManager', array(new Reference('rz.category_page.manager.category_has_page')));
            $categoryTemplates = [];
            $categoryTemplates['page'] = $container->getParameter('rz.category_page.page.template.default');
            $categoryTemplates['block'] = $container->getParameter('rz.category_page.block.template.catgory_post_list.default')['template'];
            $transformer->addMethodCall('setCategoryTemplates', array($categoryTemplates));
        }

        ########################################
        ## Inject Transformer to PostAdmin
        ########################################
        $definition = $container->getDefinition('sonata.news.admin.post');
        $definition->addMethodCall('setTransformer', array(new Reference('rz.news_page.transformer')));

        ########################################
        ## Inject Transformer to PostHasCategoryAdmin
        ########################################
        $definition = $container->getDefinition('rz.news.admin.post_has_category');
        $definition->addMethodCall('setTransformer', array(new Reference('rz.news_page.transformer')));
    }
}
