<?php

namespace Rz\NewsPageBundle\Entity;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Sonata\CoreBundle\Model\ManagerInterface;
use Sonata\NewsBundle\Model\PostInterface;
use Sonata\NewsBundle\Model\PostManagerInterface;
use Sonata\BlockBundle\Model\BlockInterface;
use Sonata\BlockBundle\Model\BlockManagerInterface;
use Sonata\ClassificationBundle\Model\CategoryManagerInterface;
use Sonata\ClassificationBundle\Model\CategoryInterface;
use Sonata\PageBundle\Model\PageManagerInterface;
use Sonata\PageBundle\Entity\BlockInteractor;
use Sonata\PageBundle\Model\Page;
use Rz\NewsPageBundle\Model\TransformerInterface;

class Transformer implements TransformerInterface
{

    protected $postManager;

    protected $pageManager;

    protected $blockManager;

    protected $postHasPageManager;

    protected $categoryManager;

    protected $permalink;

    protected $categoryPermalink;

    protected $defaultNewsPageSlug;

    protected $slugify;

    protected $blockInteractor;

    protected $postBlockService;

    protected $pageServices;

    public function __construct(PostManagerInterface $postManager,
                                PageManagerInterface $pageManager,
                                BlockManagerInterface $blockManager,
                                CategoryManagerInterface $categoryManager,
                                ManagerInterface $postHasPageManager,
                                BlockInteractor  $blockInteractor,
                                RegistryInterface $registry)
    {
        $this->postManager          = $postManager;
        $this->pageManager          = $pageManager;
        $this->blockManager         = $blockManager;
        $this->categoryManager      = $categoryManager;
        $this->postHasPageManager   = $postHasPageManager;
        $this->registry             = $registry;
        $this->blockInteractor      = $blockInteractor;
    }

    /**
     * {@inheritdoc}
     */
    public function create(PostInterface &$post)
    {
        $this->update($post);
    }

    /**
     * {@inheritdoc}
     */
    public function update(PostInterface &$post)
    {
        $emPostManager = $this->getPageManager()->getEntityManager();
        $emBlockManager = $this->getBlockManager()->getEntityManager();
        $emPostHasPageManager = $this->getPostHasPageManager()->getEntityManager();
        $emPageManager = $this->getPageManager()->getEntityManager();

        //Begin Transaction
        $emPostManager->getConnection()->beginTransaction();
        $emBlockManager->getConnection()->beginTransaction();
        $emPostHasPageManager->getConnection()->beginTransaction();
        $emPageManager->getConnection()->beginTransaction();

        try {

            #TODO: should be transaction based
            $postHasPage = $post->getPostHasPage() ?: new ArrayCollection();

            ########################################
            # Create Canonical Category Page
            ########################################
            $pageCanonicalDefaultCategory = $this->createCanonicalCategoryPage($post);

            ########################################
            # Create Post Block
            ########################################
            $postBlock = $this->createPostBlock($post);

            ########################################
            # Create Canonical Page
            ########################################
            $newsCanonicalPage = $this->createCanonicalPage($post, $postBlock, $pageCanonicalDefaultCategory);

            ########################################
            # Create Category Pages
            ########################################
            $categoryPages = $this->createCategoryPages($post, $pageCanonicalDefaultCategory);

            ########################################
            # Create Category Post
            ########################################
            $newsCategoryPages = null;
            if(count($categoryPages) > 0 && ($newsCanonicalPage && isset($newsCanonicalPage['page'])) && $postBlock) {
                $newsCategoryPages = $this->createCategoryPostPages($categoryPages, $post, $postBlock, $newsCanonicalPage['page']);
            }

            ########################################
            # Create Post Has Page
            ########################################
            if($newsCanonicalPage && isset($newsCanonicalPage['page']) && $postBlock) {
                if(count($newsCategoryPages) > 0) {
                    $newsCategoryPages = array_merge(array($newsCanonicalPage['page']->getId() => $newsCanonicalPage), $newsCategoryPages);
                } else {
                    $newsCategoryPages = array($newsCanonicalPage['page']->getId() => $newsCanonicalPage);
                }
                $this->createPostHasPage($newsCategoryPages, $post, $postBlock);
            }

            ########################################
            # check & remove delete Category
            ########################################
            $this->verifyCategoryPages($post);

            ########################################
            # update page name and slug
            ########################################
            $this->updatePages($post);

            //Rollback Transaction
            $emPostManager->getConnection()->commit();
            $emBlockManager->getConnection()->commit();
            $emPostHasPageManager->getConnection()->commit();
            $emPageManager->getConnection()->commit();


        } catch (\Exception $e) {
            //Rollback Transaction
            $emPostManager->getConnection()->rollback();
            $emBlockManager->getConnection()->rollback();
            $emPostHasPageManager->getConnection()->rollback();
            $emPageManager->getConnection()->rollback();
        }
    }

    protected function updatePages($post) {

        $em = $this->getPostManager()->getEntityManager();
        $uow = $em->getUnitOfWork();
        $uow->computeChangeSets();
        $changeset = $uow->getEntityChangeSet($post);

        //TODO: OPTIMIZE
        if(count($changeset)) {
            // Update category pages
            $postHasPage = $this->postHasPageManager->fetchCategoryPages($post);
            if(count($postHasPage)>0) {
                //update each page to trigger fix URL
                foreach($postHasPage as $php) {
                    $page = $php->getPage();
                    $page->setName($post->getTitle());
                    $page->setSlug(Page::slugify($post->getTitle()));
                    $page->setEdited(true);
                    $this->pageManager->save($page);
                }
            }
            //update canonical page
            $postHasPageCanonical = $this->postHasPageManager->fetchCanonicalPage($post);
            if($postHasPageCanonical) {
                $page = $postHasPageCanonical->getPage();
                $page->setName($post->getTitle());
                $page->setEdited(true);
                $this->pageManager->save($page);
            }
        }
    }

    protected function verifyCategoryPages($post) {
        $postHasCategories = $post->getPostHasCategory();
        if(!empty($postHasCategories)) {
            $currentCategories = [];
            // loop through each post categegory
            foreach($postHasCategories as $postHasCategory) {
                $currentCategories[] = $postHasCategory->getCategory()->getId();
            }

            $postHasPage = null;
            if(count($currentCategories)>0) {
                $postHasPage = $this->postHasPageManager->fetchCategoryPageForCleanup($post, $currentCategories);
            }

            if(count($postHasPage) >0) {
                $phpIDs = [];
                $pageIDs = [];
                foreach($postHasPage as $php) {
                    $phpIDs[] = $php->getId();
                    $pageIDs[] = $php->getPage()->getId();
                }
                //remove PostHasPage
                $this->postHasPageManager->cleanupPostHasPage($post, $phpIDs);
                //remmove Page
                $this->pageManager->cleanupPages($pageIDs);

            }
        }
    }

    protected function fetchRootCategories() {
        $rootCategories = $this->categoryManager->getRootCategories(false);
        $root = [];
        foreach($rootCategories as $category) {
            $root[] = $category->getId();
        }

        return $root;
    }

    protected function createCategoryPage(CategoryInterface $category,
                                          CategoryInterface $currentCategory,
                                          PostInterface $post,
                                          $newsCanonicalPage,
                                          $rootCategories,
                                          $parent = null){

        // check if parent has caegory
        if($parent) {
            $pageCategory = $this->pageManager->findOneBy(array('slug'=>$parent->getSlug(), 'site'=>$post->getSite()));
            if(!$pageCategory) {
                if(!in_array($parent->getId(), $rootCategories)) {
                    return $this->createCategoryPage($parent, $currentCategory, $post, $newsCanonicalPage, $rootCategories, $parent->getParent());
                }
            }
        }

        // create category page
        $pageCategory = $this->pageManager->findOneBy(array('slug'=>$category->getSlug(), 'site'=>$post->getSite()));
        if(!$pageCategory) {
            //fetch parent page
            if($parent) {
                $parentPageCategory = $this->pageManager->findOneBy(array('slug'=>$parent->getSlug(), 'site'=>$post->getSite()));
                if(!$parentPageCategory) {
                    $parentPageCategory = $this->pageManager->findOneBy(array('url'=>'/', 'site'=>$post->getSite()));
                }
            }
            $pageCategory = $this->createPage($post, $parentPageCategory, $newsCanonicalPage, $category->getName(), null, $this->getPageService('category'));
            #TODO insert data to category_page
        }

        if($currentCategory->getId() === $category->getId()) {
            return $pageCategory;
        }

        return;
    }

    protected function createCategoryPages($post, $pageCanonicalDefaultCategory) {
        $categoryPages = [];
        #TODO: transfer some process to use notification
        #TODO: should be able to control Category Parent Page
        // create the base page for categories
        $postHasCategories = $post->getPostHasCategory();
        $rootCategories = $this->fetchRootCategories();
        //generate category pages if post has category
        if(!empty($postHasCategories)) {
            // loop through each post categegory
            foreach($postHasCategories as $postHasCategory) {
                $currentCat = $postHasCategory->getCategory();
                //fetch parent categories of current category
                $cats = $this->postHasPageManager->categoryParentWalker($currentCat, $cats);
                krsort($cats);
                //traverse through current category tree
                foreach($cats as $cat) {
                    $page = $this->createCategoryPage($cat['category'], $currentCat, $postHasCategory->getPost(), $pageCanonicalDefaultCategory, $rootCategories, $cat['parent']);
                    // create Post has Page
                    if($page) {
                        $categoryPages[$cat['category']->getId()]['page'] = $page;
                        $categoryPages[$cat['category']->getId()]['category'] = $cat['category'];
                    }
                }
            }
        }
        return $categoryPages;
    }

    protected function createCanonicalPage($post, $postBlock, $pageCanonicalDefaultCategory) {
        //check if canonical page exist
        $postHasPage = $this->getPostHasPageManager()->findOneByPageAndPageHasPost(array('post'=>$post, 'parent'=>$pageCanonicalDefaultCategory)) ?: null;

        if(!$postHasPage) {
            // create canonical page
            $newsCanonicalPage = $this->createPage($post, $pageCanonicalDefaultCategory, null, $post->getTitle(), Page::slugify($post->getId().' '.$post->getTitle()), $this->getPageService('post_canonical'));

            // create container block
            $newsCanonicalPage->addBlocks($contentContainer = $this->getBlockInteractor()->createNewContainer(array(
                'enabled' => true,
                'page' => $newsCanonicalPage,
                'code' => 'content',
            )));
            $contentContainer->setName('The post content container');
            $this->getBlockManager()->save($contentContainer);

            // create shared block
            $contentContainer->addChildren($sharedBlock = $this->getBlockManager()->create());
            $sharedBlock->setType('sonata.page.block.shared_block');
            $sharedBlock->setName(sprintf('%s - %s', 'Shared Block', $post->getTitle()));
            $sharedBlock->setSetting('blockId',$postBlock->getId());
            $sharedBlock->setPosition(1);
            $sharedBlock->setEnabled(true);
            $sharedBlock->setPage($newsCanonicalPage);
            $this->getPageManager()->save($newsCanonicalPage);

            return array('page'=>$newsCanonicalPage, 'shared_block'=>$sharedBlock);

        } else {
            return array('page'=>$postHasPage->getPage(), 'shared_block'=>$postHasPage->getSharedBlock());
        }
    }

    protected function createPage($post, $parent, $newsCanonicalPage=null, $name='PAGE', $slug=null, $pageType=null) {
        $page = $this->pageManager->findOneBy(array('name'=>$name, 'parent'=>$parent, 'site'=>$post->getSite()));
        if(!$page) {
            $page = $this->pageManager->create();
            $page->setEnabled(true);
            $page->setName($name);
            $page->setRouteName(\Sonata\PageBundle\Model\PageInterface::PAGE_ROUTE_CMS_NAME);
            $page->setPosition(1);
            $page->setDecorate(true);
            $page->setRequestMethod('GET|POST|HEAD|DELETE|PUT');
            $page->setSite($post->getSite());
            $page->setCanonicalPage($newsCanonicalPage);
            $page->setParent($parent);

            if($pageType) {
                $page->setType($pageType);
            }

            if(!$newsCanonicalPage) {
                $page->setSlug($slug);
            }

            if ($post->getSetting('pageTemplateCode')) {
                $page->setTemplateCode($post->getSetting('pageTemplateCode'));
            }
            $page = $this->pageManager->save($page);
        }
        return $page;
    }

    protected function createCanonicalCategoryPage($post) {
        $pageCanonicalDefaultCategory = $this->pageManager->findOneBy(array('slug'=>$this->getDefaultNewsPageSlug(), 'site'=>$post->getSite()));
        if(!$pageCanonicalDefaultCategory) {
            #TODO home URL should be in a parameter
            $parent = $this->pageManager->findOneBy(array('url'=>'/', 'site'=>$post->getSite()));
            $pageCanonicalDefaultCategory = $this->createPage($post, $parent, null, $this->getDefaultNewsPageSlug(), null, $this->getPageService('category_canonical'));
        }
        return $pageCanonicalDefaultCategory;
    }

    protected function createPostBlock($post) {
        $postBlock = null;
        //check if block is existing on Post Has Page
        $postHasPage = $this->getPostHasPageManager()->findOneBy(array('post'=>$post)) ?: null;

        if($postHasPage && $postHasPage->getBlock()) {
            return $postHasPage->getBlock();
        }

        $postBlock = $this->getBlockManager()->create();
        $postBlock->setType($this->getPostBlockService());
        $postBlock->setName(sprintf('%s - %s', 'Post Block', $post->getTitle()));
        $postBlock->setSetting('postId', $post->getId());
        $postBlock->setSetting('template', $post->getSetting('template'));
        $postBlock = $this->getBlockManager()->save($postBlock);
        return $postBlock;
    }

    protected function createCategoryPostPages($categoryPages, $post, $postBlock, $canonicalPage = null) {
        $newsCategoryPages = [];
        foreach($categoryPages as $catPage) {
            $postHasPage = $this->getPostHasPageManager()->findOneByPageAndPageHasPost(array('post'=>$post, 'parent'=>$catPage['page'])) ?: null;
            if(!$postHasPage) {
                // create category post page
                $newsCategoryPage = $this->createPage($post, $catPage['page'], $canonicalPage, $post->getTitle(), null, $this->getPageService('post'));
                // create container block
                $newsCategoryPage->addBlocks($contentContainer = $this->getBlockInteractor()->createNewContainer(array(
                    'enabled' => true,
                    'page' => $newsCategoryPage,
                    'code' => 'content',
                )));
                $contentContainer->setName('The post content container');
                $this->getBlockManager()->save($contentContainer);

                // create shared block
                $contentContainer->addChildren($sharedBlock = $this->getBlockManager()->create());
                $sharedBlock->setType('sonata.page.block.shared_block');
                $sharedBlock->setName(sprintf('%s - %s', 'Shared Block', $post->getTitle()));
                $sharedBlock->setSetting('blockId',$postBlock->getId());
                $sharedBlock->setPosition(1);
                $sharedBlock->setEnabled(true);
                $sharedBlock->setPage($newsCategoryPage);
                $this->getPageManager()->save($newsCategoryPage);

                $newsCategoryPages[$catPage['page']->getId()]['page'] = $newsCategoryPage;
                $newsCategoryPages[$catPage['page']->getId()]['shared_block'] = $sharedBlock;
                $newsCategoryPages[$catPage['page']->getId()]['category'] = $catPage['category'];

            } else {
                $newsCategoryPages[$postHasPage->getId()]['page'] = $postHasPage->getPage();
                $newsCategoryPages[$postHasPage->getId()]['shared_block'] = $postHasPage->getSharedBlock();
                $newsCategoryPages[$postHasPage->getId()]['category'] =  $postHasPage->getCategory() ?: $catPage['category'];
            }
        }

        return $newsCategoryPages;
    }

    protected function createPostHasPage($newsCategoryPages, $post, $postBlock) {
        $postHasPage = null;
        foreach($newsCategoryPages as $catPage) {

            $php = $this->getPostHasPageManager()->findOneBy(array('post'=>$post, 'page'=>$catPage['page']));
            if(!$php) {
                $php = $this->getPostHasPageManager()->create();
                $php->setPost($post);
                $php->setPage($catPage['page']);
                $php->setBlock($postBlock);
                $php->setSharedBlock($catPage['shared_block']);
                $category = isset($catPage['category']) ? $catPage['category'] : null;
                $php->setCategory($category);
                $isCanonical = $catPage['page']->getCanonicalPage() ? false : true;
                $php->setIsCanonical($isCanonical);
                $this->getPostHasPageManager()->save($php);
                $post->addPostHasPage($php);
            }
        }
    }

    /**
     * @return mixed
     */
    public function getPermalink()
    {
        return $this->permalink;
    }

    /**
     * @param mixed $permalink
     */
    public function setPermalink($permalink)
    {
        $this->permalink = $permalink;
    }

    /**
     * @return mixed
     */
    public function getCategoryPermalink()
    {
        return $this->categoryPermalink;
    }

    /**
     * @param mixed $categoryPermalink
     */
    public function setCategoryPermalink($categoryPermalink)
    {
        $this->categoryPermalink = $categoryPermalink;
    }

    /**
     * @return PostHasPageInterface
     */
    public function getPostHasPageManager()
    {
        return $this->postHasPageManager;
    }

    /**
     * @param PostHasPageInterface $postHasPageManager
     */
    public function setPostHasPageManager($postHasPageManager)
    {
        $this->postHasPageManager = $postHasPageManager;
    }

    /**
     * @return mixed
     */
    public function getCategoryManager()
    {
        return $this->categoryManager;
    }

    /**
     * @param mixed $categoryManager
     */
    public function setCategoryManager($categoryManager)
    {
        $this->categoryManager = $categoryManager;
    }

    /**
     * @return mixed
     */
    public function getDefaultNewsPageSlug()
    {
        return $this->defaultNewsPageSlug;
    }

    /**
     * @param mixed $defaultNewsPageSlug
     */
    public function setDefaultNewsPageSlug($defaultNewsPageSlug)
    {
        $this->defaultNewsPageSlug = $this->getSlugify()->slugify($defaultNewsPageSlug);
    }

    /**
     * @return mixed
     */
    public function getSlugify()
    {
        return $this->slugify;
    }

    /**
     * @param mixed $slugify
     */
    public function setSlugify($slugify)
    {
        $this->slugify = $slugify;
    }

    /**
     * @return BlockManagerInterface
     */
    public function getBlockManager()
    {
        return $this->blockManager;
    }

    /**
     * @param BlockManagerInterface $blockManager
     */
    public function setBlockManager($blockManager)
    {
        $this->blockManager = $blockManager;
    }

    /**
     * @return PostManagerInterface
     */
    public function getPostManager()
    {
        return $this->postManager;
    }

    /**
     * @param PostManagerInterface $postManager
     */
    public function setPostManager($postManager)
    {
        $this->postManager = $postManager;
    }

    /**
     * @return PageManagerInterface
     */
    public function getPageManager()
    {
        return $this->pageManager;
    }

    /**
     * @param PageManagerInterface $pageManager
     */
    public function setPageManager($pageManager)
    {
        $this->pageManager = $pageManager;
    }

    /**
     * @return mixed
     */
    public function getBlockInteractor()
    {
        return $this->blockInteractor;
    }

    /**
     * @param mixed $blockInteractor
     */
    public function setBlockInteractor(BlockInteractor $blockInteractor)
    {
        $this->blockInteractor = $blockInteractor;
    }

    /**
     * @return mixed
     */
    public function getPostBlockService()
    {
        return $this->postBlockService;
    }

    /**
     * @param mixed $postBlockService
     */
    public function setPostBlockService($postBlockService)
    {
        $this->postBlockService = $postBlockService;
    }

    /**
     * @return mixed
     */
    public function getPageServices()
    {
        return $this->pageServices;
    }

    /**
     * @param mixed $pageServices
     */
    public function setPageServices($pageServices)
    {
        $this->pageServices = $pageServices;
    }

    public function getPageService($name, $default= null) {
        return isset($this->pageServices[$name]) ? $this->pageServices[$name] : $default;
    }
}
