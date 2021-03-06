<?php

namespace NeoPHP\Controllers;

use ReflectionClass;
use ReflectionException;

/**
 * Class Controllers
 * @package NeoPHP\mvc\controllers
 */
abstract class Controllers {

    private static $controllers = [];

    /**
     * Returns a controller instance
     * @param string $controllerClass
     * @return mixed controller instance
     * @throws ReflectionException
     */
    public static function get($controllerClass) {
        if (!isset(self::$controllers[$controllerClass])) {
            if (class_exists($controllerClass)) {
                $reflectionClass = new ReflectionClass($controllerClass);
                self::$controllers[$controllerClass] = $reflectionClass->isAbstract()? $controllerClass : (new $controllerClass);
            }
            else {
                self::$controllers[$controllerClass] = null;
            }
        }
        return self::$controllers[$controllerClass];
    }
}