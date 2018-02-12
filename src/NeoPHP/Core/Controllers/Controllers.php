<?php

namespace NeoPHP\Core\Controllers;

/**
 * Class Controllers
 * @package NeoPHP\mvc\controllers
 */
abstract class Controllers {

    private static $controllers = [];

    /**
     * @param $controllerClass
     * @return mixed
     */
    public static function getController($controllerClass) {
        if (!isset(self::$controllers[$controllerClass])) {
            self::$controllers[$controllerClass] = new $controllerClass;
        }
        return self::$controllers[$controllerClass];
    }
}