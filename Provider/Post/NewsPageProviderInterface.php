<?php

namespace Rz\NewsPageBundle\Provider\Post;

interface NewsPageProviderInterface
{
    /**
     * @return array
     */
    public function getTemplates();

    /**
     * @param array $templates
     */
    public function setTemplates($templates);

    /**
     * @return mixed
     */
    public function getIsControllerEnabled();

    /**
     * @param mixed $isControllerEnabled
     */
    public function setIsControllerEnabled($isControllerEnabled);

    /**
     * @return mixed
     */
    public function getDefaultTemplate();

    /**
     * @param mixed $defaultTemplate
     */
    public function setDefaultTemplate($defaultTemplate);

    public function getPreferedChoice();
}
