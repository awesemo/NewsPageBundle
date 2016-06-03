<?php

namespace Rz\NewsPageBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $node = $treeBuilder->root('rz_news_page');
        $this->addNewsPageSection($node);
        $this->addSettingsSection($node);
        $this->addPermalinkSection($node);
        $this->addProviderSection($node);
        return $treeBuilder;
    }

    /**
     * @param \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition $node
     */
    private function addSettingsSection(ArrayNodeDefinition $node)
    {
        $node
            ->children()
                ->scalarNode('slugify_service')
                    ->info('You should use: sonata.core.slugify.cocur, but for BC we keep \'sonata.core.slugify.native\' as default')
                    ->defaultValue('sonata.core.slugify.cocur')
                ->end()
                ->scalarNode('enable_controller')->defaultValue(false)->end()
            ->end()
        ;
    }

    /**
     * @param \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition $node
     */
    private function addNewsPageSection(ArrayNodeDefinition $node)
    {
        $node
            ->children()
                ->arrayNode('page')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('parent_slug')->cannotBeEmpty()->defaultValue('article')->end()
                        ->arrayNode('templates')
                            ->useAttributeAsKey('id')
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('name')->isRequired()->end()
                                    ->scalarNode('template_code')->isRequired()->end()
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('transformer')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('class')->cannotBeEmpty()->defaultValue('Rz\\NewsPageBundle\\Entity\\Transformer')->end()
                            ->end()
                        ->end()
                        ->arrayNode('services')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->arrayNode('post')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->scalarNode('service')->defaultValue('rz.news_page.page.service.post')->end()
                                    ->end()
                                ->end()
                                ->arrayNode('post_canonical')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->scalarNode('service')->defaultValue('rz.news_page.page.service.post_canonical')->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('manager_class')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('orm')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('post_has_page')->defaultValue('Rz\\NewsPageBundle\\Entity\\PostHasPageManager')->end()
                                ->scalarNode('post')->defaultValue('Rz\\NewsPageBundle\\Entity\\PostManager')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('admin')
                    ->addDefaultsIfNotSet()
                    ->children()
                       ->arrayNode('post_has_page')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('class')->cannotBeEmpty()->defaultValue('Rz\\NewsPageBundle\\Admin\\PostHasPageAdmin')->end()
                                ->scalarNode('controller')->cannotBeEmpty()->defaultValue('SonataAdminBundle:CRUD')->end()
                                ->scalarNode('translation')->cannotBeEmpty()->defaultValue('SonataNewsBundle')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('class')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('post_has_page')->defaultValue('AppBundle\\Entity\\NewsPage\\PostHasPage')->end()
                        ->scalarNode('page')->defaultValue('AppBundle\\Entity\\Page\\Page')->end()
                        ->scalarNode('site')->defaultValue('AppBundle\\Entity\\Page\\Site')->end()
                        ->scalarNode('block')->defaultValue('AppBundle\\Entity\\Page\\Block')->end()
                        ->scalarNode('post')->defaultValue('AppBundle\\Entity\\News\\Post')->end()
                        ->scalarNode('category')->defaultValue('AppBundle\\Entity\\Classification\\Category')->end()
                    ->end()
                ->end()
                ->arrayNode('block')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('service')->defaultValue('rz.news_page.block.post')->cannotBeEmpty()->end()
                        ->arrayNode('template')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('name')->defaultValue('Default')->cannotBeEmpty()->end()
                                ->scalarNode('path')->defaultValue('RzNewsPageBundle:Block:block_post_default.html.twig')->cannotBeEmpty()->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('page_service')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('post')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('name')->defaultValue('Post')->end()
                                ->scalarNode('class')->defaultValue('Rz\\NewsPageBundle\\Page\\Service\\PostCanonicalPageService')->end()
                            ->end()
                        ->end()
                        ->arrayNode('post_canonical')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('name')->defaultValue('Post Canonical')->end()
                                ->scalarNode('class')->defaultValue('Rz\\NewsPageBundle\\Page\\Service\\PostCanonicalPageService')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    /**
     * @param \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition $node
     */
    private function addPermalinkSection(ArrayNodeDefinition $node)
    {
         $node
            ->children()
                ->arrayNode('permalink')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('default')->isRequired()->end()
                        ->arrayNode('permalinks')
                            ->useAttributeAsKey('id')
                            ->isRequired()
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('permalink')->isRequired()->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

       /**
     * @param \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition $node
     */
    private function addProviderSection(ArrayNodeDefinition $node)
    {
        $node
            ->children()
                ->arrayNode('providers')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('seo')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('default_provider')->defaultValue('rz.news.provider.seo.default')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }
}
