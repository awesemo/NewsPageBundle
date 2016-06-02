<?php

namespace Rz\NewsPageBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Rz\NewsPageBundle\DependencyInjection\Compiler\OverrideServiceCompilerPass;
use Rz\NewsPageBundle\DependencyInjection\Compiler\NewsPageCompilerPass;

class RzNewsPageBundle extends Bundle
{

    /**
     * {@inheritDoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new OverrideServiceCompilerPass());
        if (interface_exists('Sonata\PageBundle\Model\PageInterface')) {
            $container->addCompilerPass(new NewsPageCompilerPass());
        }
    }
}
