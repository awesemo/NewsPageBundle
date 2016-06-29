<?php

namespace Rz\NewsPageBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class OverrideServiceCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {

        #####################################
        ## Override Entity Manager
        #####################################
        $definition = $container->getDefinition('sonata.news.manager.post');
        $definition->setClass($container->getParameter('rz.news_page.entity.manager.post.class'));

        #set slugify service
        $serviceId = $container->getParameter('rz.news_page.slugify_service');
        ########################################
        ## PostAdmin
        ########################################
        $definition = $container->getDefinition('sonata.news.admin.post');
        #set slugify service
        $definition->addMethodCall('setSlugify', array(new Reference($serviceId)));
        $definition->addMethodCall('setIsControllerEnabled', array($container->getParameter('rz.news_page.enable_controller')));
        if(!$container->getParameter('rz.news_page.enable_controller')) {
            $definition->addMethodCall('setPageTemplates', array($container->getParameter('rz.news_page.page.templates')));
        }

        ########################
        # Post Provider
        ########################
        $pool = $container->getDefinition('rz.news.post.pool');

        if (interface_exists('Sonata\PageBundle\Model\BlockInteractorInterface')) {
            $blocks = $container->getParameter('sonata_block.blocks');
            $blockService = $container->getParameter('rz.news_page.post_block_service');
            if(isset($blocks[$blockService]) && isset($blocks[$blockService]['templates'])) {
                $container->setParameter('rz.news_page.post_templates', $blocks[$blockService]['templates']);
            }
        }

        $postTemplates = $container->getParameter('rz.news_page.post_templates');

        $collections = $container->getParameter('rz.news.post.provider.collections');

        $templates = [];
        foreach ($postTemplates as $item) {
            $templates[$item['template']] = $item['name'];
        }

        foreach ($collections as $name => $settings) {
            if($container->hasDefinition($settings['provider'])) {
                $provider =$container->get($settings['provider']);
                if ($provider instanceof \Rz\NewsPageBundle\Provider\Post\NewsPageProviderInterface) {
                    $definition = $container->getDefinition($settings['provider']);
                    $definition->addMethodCall('setTemplates', array($templates));
                    $definition->addMethodCall('setIsControllerEnabled', array($container->getParameter('rz.news_page.enable_controller')));
                }
            }
        }

        ########################################
        ## PostHasCategoryAdmin
        ########################################
        $definition = $container->getDefinition('rz.news.admin.post_has_category');
        $definition->addMethodCall('setSiteManager', array(new Reference('sonata.page.manager.site')));
    }
}
