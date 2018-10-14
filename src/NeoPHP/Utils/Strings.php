<?php

namespace NeoPHP\Utils;

abstract class Strings {

    /**
     * Indica si un texte comienza con un texto en particular
     * @param $haystack
     * @param $needle
     * @return bool
     */
    public static function startsWith($haystack, $needle) {
        $length = strlen($needle);
        return (substr($haystack, 0, $length) === $needle);
    }

    /**
     * Indica si un texte termina con un texto en particular
     * @param $haystack
     * @param $needle
     * @return bool
     */
    public static function endsWith($haystack, $needle) {
        $length = strlen($needle);
        return $length === 0 || (substr($haystack, -$length) === $needle);
    }
}