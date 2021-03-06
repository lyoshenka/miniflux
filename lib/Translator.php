<?php

namespace Translator {

    const PATH = 'locales/';


    function translate($identifier)
    {
        $args = \func_get_args();

        \array_shift($args);
        \array_unshift($args, get($identifier, $identifier));

        foreach ($args as &$arg) {
            $arg = htmlspecialchars($arg, ENT_QUOTES, 'UTF-8', false);
        }

        return \call_user_func_array(
            'sprintf',
            $args
        );
    }


    function translate_no_escaping($identifier)
    {
        $args = \func_get_args();

        \array_shift($args);
        \array_unshift($args, get($identifier, $identifier));

        return \call_user_func_array(
            'sprintf',
            $args
        );
    }


    function number($number)
    {
        return number_format(
            $number,
            get('number.decimals', 2),
            get('number.decimals_separator', '.'),
            get('number.thousands_separator', ',')
        );
    }


    function currency($amount)
    {
        $position = get('currency.position', 'before');
        $symbol = get('currency.symbol', '$');
        $str = '';

        if ($position === 'before') {

            $str .= $symbol;
        }

        $str .= number($amount);

        if ($position === 'after') {

            $str .= ' '.$symbol;
        }

        return $str;
    }


    function datetime($format, $timestamp)
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') {
            $format = preg_replace('#(?<!%)((?:%%)*)%e#', '\1%#d', $format);
            $format = preg_replace('#(?<!%)((?:%%)*)%k#', '\1%#H', $format);
        }
        
        return strftime($format, (int) $timestamp);
    }


    function get($identifier, $default = '')
    {
        $locales = container();

        if (isset($locales[$identifier])) {

            return $locales[$identifier];
        }
        else {

            return $default;
        }
    }


    function load($language)
    {
        setlocale(LC_TIME, $language.'.UTF-8');

        $path = PATH.$language;
        $locales = array();

        if (is_dir($path)) {

            $dir = new \DirectoryIterator($path);

            foreach ($dir as $fileinfo) {

                if (strpos($fileinfo->getFilename(), '.php') !== false) {

                    $locales = array_merge($locales, include $fileinfo->getPathname());
                }
            }
        }

        container($locales);
    }


    function container($locales = null)
    {
        static $values = array();

        if ($locales !== null) {

            $values = $locales;
        }

        return $values;
    }
}

namespace {

    function tne() {
        return call_user_func_array('\Translator\translate_no_escaping', func_get_args());
    }

    function t() {
        return call_user_func_array('\Translator\translate', func_get_args());
    }

    function c() {
        return call_user_func_array('\Translator\currency', func_get_args());
    }

    function n() {
        return call_user_func_array('\Translator\number', func_get_args());
    }

    function dt() {
        return call_user_func_array('\Translator\datetime', func_get_args());
    }
}
