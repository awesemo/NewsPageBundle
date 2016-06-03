<?php

namespace Rz\NewsPageBundle\Model;

use Sonata\PageBundle\Entity\BlockInteractor;
use Sonata\CoreBundle\Model\ManagerInterface;
use Sonata\BlockBundle\Block\BlockServiceInterface;

interface TransformerInterface
{

    /**
     * @return PostHasPageInterface
     */
    public function getPostHasPageManager();

    /**
     * @param PostHasPageInterface $postHasPageManager
     */
    public function setPostHasPageManager(ManagerInterface $postHasPageManager);

    /**
     * @return mixed
     */
    public function getCategoryHasPageManager();

    /**
     * @param mixed $categoryHasPageManager
     */
    public function setCategoryHasPageManager(ManagerInterface $categoryHasPageManager);

    /**
     * @return mixed
     */
    public function getCategoryManager();

    /**
     * @param mixed $categoryManager
     */
    public function setCategoryManager(ManagerInterface $categoryManager);

    /**
     * @return mixed
     */
    public function getDefaultNewsPageSlug();

    /**
     * @param mixed $defaultNewsPageSlug
     */
    public function setDefaultNewsPageSlug($defaultNewsPageSlug);

    /**
     * @return mixed
     */
    public function getSlugify();

    /**
     * @param mixed $slugify
     */
    public function setSlugify($slugify);

    /**
     * @return BlockManagerInterface
     */
    public function getBlockManager();

    /**
     * @param BlockManagerInterface $blockManager
     */
    public function setBlockManager(ManagerInterface $blockManager);

    /**
     * @return PostManagerInterface
     */
    public function getPostManager();

    /**
     * @param PostManagerInterface $postManager
     */
    public function setPostManager(ManagerInterface $postManager);

    /**
     * @return PageManagerInterface
     */
    public function getPageManager();

    /**
     * @param PageManagerInterface $pageManager
     */
    public function setPageManager(ManagerInterface $pageManager);

    /**
     * @return mixed
     */
    public function getBlockInteractor();

    /**
     * @param mixed $blockInteractor
     */
    public function setBlockInteractor(BlockInteractor $blockInteractor);

    /**
     * @return mixed
     */
    public function getPostBlockService();

    /**
     * @param mixed $postBlockService
     */
    public function setPostBlockService($postBlockService);

    /**
     * @return mixed
     */
    public function getCategoryPostListService();

    /**
     * @param mixed $categoryPostListService
     */
    public function setCategoryPostListService($categoryPostListService);

    /**
     * @return mixed
     */
    public function getPageServices();

    /**
     * @param mixed $pageServices
     */
    public function setPageServices($pageServices);

    public function getPageService($name, $default= null);
}
