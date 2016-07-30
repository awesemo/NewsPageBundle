<?php
namespace Rz\NewsPageBundle\Model;

use Sonata\NewsBundle\Model\PostInterface;
use Sonata\PageBundle\Model\PageInterface;
use Rz\CoreBundle\Model\RelationModelInterface;

interface PostHasPageInterface extends RelationModelInterface
{
    /**
     * @return mixed
     */
    public function getPage();

    /**
     * @param mixed $category
     */
    public function setPage(PageInterface $page);

    /**
     * @return mixed
     */
    public function getPost();

    /**
     * @param mixed $post
     */
    public function setPost(PostInterface $post);
}
