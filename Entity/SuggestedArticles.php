<?php

namespace Rz\NewsPageBundle\Entity;

use Rz\NewsBundle\Entity\BaseSuggestedArticles;
use Rz\NewsPageBundle\Model\PostHasPageInterface;

abstract class SuggestedArticles extends BaseSuggestedArticles
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
