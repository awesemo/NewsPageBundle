<?php

namespace Rz\NewsPageBundle\Entity;

use Rz\NewsBundle\Entity\BaseRelatedArticles;
use Rz\NewsPageBundle\Model\PostHasPageInterface;

abstract class RelatedArticles extends BaseRelatedArticles
{

    protected $postHasPage;

    /**
     * @return mixed
     */
    public function getPostHasPage()
    {
        return $this->postHasPage;
    }

    /**
     * @param mixed $postHasPage
     */
    public function setPostHasPage($postHasPage)
    {
        $this->postHasPage = $postHasPage;
    }


    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return $this->getPost()->getTitle() ?: 'n/a';
    }
}
