<?php

namespace Rz\NewsPageBundle\Entity;

use Rz\NewsBundle\Entity\PostManager as BasePostManager;
use Doctrine\ORM\Query\Expr\Join;
use Sonata\ClassificationBundle\Model\CollectionInterface;
use Sonata\DatagridBundle\Pager\Doctrine\Pager;
use Sonata\DatagridBundle\ProxyQuery\Doctrine\ProxyQuery;
use Sonata\NewsBundle\Model\BlogInterface;
use Sonata\NewsBundle\Model\PostInterface;
use Sonata\NewsBundle\Model\PostManagerInterface;

class PostManager extends BasePostManager
{
}
