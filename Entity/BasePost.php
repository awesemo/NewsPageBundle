<?php

namespace Rz\NewsPageBundle\Entity;

use Rz\NewsBundle\Entity\BasePost as Post;
use Doctrine\Common\Collections\ArrayCollection;
use Rz\NewsBundle\Model\PostHasCategoryInterface;
use Rz\NewsBundle\Model\PostHasMediaInterface;
use Rz\NewsBundle\Model\RelatedArticlesInterface;
use Rz\NewsBundle\Model\SuggestedArticlesInterface;
use Rz\NewsPageBundle\Model\PostHasPageInterface;


abstract class BasePost extends Post
{
    protected $seoSettings;
    protected $postHasPage;
    protected $site;

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        parent::__construct();
        $this->postHasPage = new ArrayCollection();
    }

    /**
     * @return mixed
     */
    public function getSeoSettings()
    {
        return $this->seoSettings;
    }

    /**
     * @param mixed $settings
     */
    public function setSeoSettings($seoSettings)
    {
        $this->seoSettings = $seoSettings;
    }

    /**
     * {@inheritDoc}
     */
    public function getSeoSetting($name, $default = null)
    {
        return isset($this->seoSettings[$name]) ? $this->seoSettings[$name] : $default;
    }

    /**
     * {@inheritDoc}
     */
    public function setSeoSetting($name, $value)
    {
        $this->seoSettings[$name] = $value;
    }

    /**
     * @param mixed $postHasPage
     */
    public function setPostHasPage($postHasPage)
    {
        $this->postHasPage = new ArrayCollection();
        foreach ($postHasPage as $child) {
            $this->addPostHasPage($child);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function addPostHasPage(PostHasPageInterface $postHasPage)
    {
        $postHasPage->setPost($this);
        $this->postHasPage[] = $postHasPage;
    }

    /**
     * @return mixed
     */
    public function getPostHasPage()
    {
        return $this->postHasPage;
    }

    /**
     * {@inheritdoc}
     */
    public function removePostHasPage(PostHasPageInterface $childToDelete)
    {
        foreach ($this->getPostHasPage() as $pos => $child) {
            if ($childToDelete->getId() && $child->getId() === $childToDelete->getId()) {
                unset($this->postHasPage[$pos]);

                return;
            }

            if (!$childToDelete->getId() && $child === $childToDelete) {
                unset($this->postHasPage[$pos]);

                return;
            }
        }
    }

    /**
     * @return mixed
     */
    public function getSite()
    {
        return $this->site;
    }

    /**
     * @param mixed $site
     */
    public function setSite($site)
    {
        $this->site = $site;
    }
}
