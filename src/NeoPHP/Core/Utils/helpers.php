<?php

if (!function_exists('getApp')) {
    /**
     * @return \NeoPHP\Core\Application
     */
    function getApp() {
        return \NeoPHP\Core\Application::getInstance();
    }
}

if (!function_exists('getController')) {
    /**
     * @param $controllerClass
     * @return mixed
     */
    function getController($controllerClass) {
        return \NeoPHP\Core\Controllers\Controllers::get($controllerClass);
    }
}

if (!function_exists('getProperty')) {
    /**
     * @param $key
     * @param null $defaultValue
     * @return mixed
     */
    function getProperty($key, $defaultValue=null) {
        return \NeoPHP\Config\Properties::get($key, $defaultValue);
    }
}

if (!function_exists('getLogger')) {
    /**
     * @param null $loggerName
     * @return \Monolog\Logger
     */
    function getLogger($loggerName=null) {
        return \NeoPHP\Log\Loggers::get($loggerName);
    }
}

if (!function_exists('handleError')) {
    /**
     * @param $errno
     * @param $errstr
     * @param $errfile
     * @param $errline
     * @param $errcontext
     * @throws ErrorException
     */
    function handleError($errno, $errstr, $errfile, $errline, $errcontext) {
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    }
}

if (!function_exists('handleException')) {
    /**
     * @param $ex
     */
    function handleException($ex) {

        getLogger()->error($ex);
        $whoops = new \Whoops\Run;
        if (getProperty("app.debug")) {
            $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
        }
        else {
            $whoops->pushHandler(new \Whoops\Handler\PlainTextHandler());
        }
        $whoops->handleException($ex);
    }
}

