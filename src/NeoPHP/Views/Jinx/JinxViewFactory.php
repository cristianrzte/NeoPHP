<?php

namespace NeoPHP\Views\Jinx;

use NeoPHP\Views\View;
use NeoPHP\Views\ViewFactory;

/**
 * Class JinxViewFactory
 * @package NeoPHP\Views\Jinx
 */
class JinxViewFactory extends ViewFactory {

    public function createView($name, array $parameters = []): View {
        $templatesPath = $this->has("templatesPath")? $this->get("templatesPath") : get_app()->resourcesPath() . DIRECTORY_SEPARATOR . "views";
        $compiledTemplatesPath = $this->has("compiledTemplatesPath")? $this->get("compiledTemplatesPath") : get_app()->storagePath() . DIRECTORY_SEPARATOR . "framework" . DIRECTORY_SEPARATOR . "views";
        return new JinxView($templatesPath, $compiledTemplatesPath, $name, $parameters);
    }
}