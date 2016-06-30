<?php

namespace Rz\NewsPageBundle\Model;

use Sonata\ClassificationBundle\Model\CategoryInterface;
use Sonata\NewsBundle\Model\PostInterface;
use Sonata\PageBundle\Model\PageInterface;
use Rz\CoreBundle\Model\RelationModel;

abstract class PostHasPage extends RelationModel implements PostHasPageInterface
{
    protected $position;
    protected $updatedAt;
    protected $createdAt;
    protected $enabled;
    protected $page;
    protected $post;
    protected $block;
    protected $isCanonical;
    protected $sharedBlock;
    protected $category;

    public function __construct()
    {
        $this->position = 0;
        $this->enabled  = true;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return $this->getPost()->getTitle() ?: 'n/a';
    }

    public function getPage()
    {
        return $this->page;
    }

    public function setPage(PageInterface $page)
    {
        $this->page = $page;
    }

    /**
     * @return mixed
     */
    public function getPost()
    {
        return $this->post;
    }

    /**
     * @param mixed $post
     */
    public function setPost(PostInterface $post)
    {
        $this->post = $post;
    }

    /**
     * @return mixed
     */
    public function getBlock()
    {
        return $this->block;
    }

    /**
     * @param mixed $block
     */
    public function setBlock($block)
    {
        $this->block = $block;
    }

    /**
     * @return mixed
     */
    public function getSharedBlock()
    {
        return $this->sharedBlock;
    }

    /**
     * @param mixed $sharedBlock
     */
    public function setSharedBlock($sharedBlock)
    {
        $this->sharedBlock = $sharedBlock;
    }

    /**
     * @return mixed
     */
    public function getIsCanonical()
    {
        return $this->isCanonical;
    }

    /**
     * @param mixed $isCanonical
     */
    public function setIsCanonical($isCanonical)
    {
        $this->isCanonical = $isCanonical;
    }

    /**
     * @return mixed
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param mixed $category
     */
    public function setCategory($category)
    {
        $this->category = $category;
    }
}