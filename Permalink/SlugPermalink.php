<?php

namespace Rz\NewsPageBundle\Permalink;

use Sonata\NewsBundle\Model\PostInterface;
use Sonata\NewsBundle\Permalink\PermalinkInterface;

class SlugPermalink implements PermalinkInterface
{
    /**
     * {@inheritdoc}
     */
    public function generate(PostInterface $post)
    {
        return $post->getSlug();
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

        list($slug) = $parameters;

        return array(
            'slug'  => $slug
        );
    }
}
