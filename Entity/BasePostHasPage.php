<?php

namespace Rz\NewsPageBundle\Entity;

use Rz\NewsPageBundle\Model\PostHasPage;

abstract class BasePostHasPage extends PostHasPage
{
    /**
     * Pre Persist method
     */
    public function prePersist()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    /**
     * Pre Update method
     */
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime();
    }
}
