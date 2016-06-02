<?php

namespace Rz\NewsPageBundle\Permalink;

use Sonata\NewsBundle\Model\PostInterface;
use Sonata\NewsBundle\Permalink\PermalinkInterface;

class DefaultPermalink implements PermalinkInterface
{
    /**
     * {@inheritdoc}
     */
    public function generate(PostInterface $post)
    {
        return $post->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function getParameters($permalink)
    {
        $parameters = explode('/', $permalink);

        if (count($parameters) > 1 || count($parameters) == 0) {
            throw new \InvalidArgumentException('wrong permalink format');
        }

        list($id) = $parameters;

        return array(
            'id'  => $id
        );
    }
}
