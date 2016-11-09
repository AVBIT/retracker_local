<?php
/**
 * ----------------------------------------------------------------------------
 *                              LANGUAGE DETECT CLASS
 *              (determining the preferred language of the user)
 * ----------------------------------------------------------------------------
 * LangDetect class using Singleton pattern.
 * Only one instance of the class will be made, this requires less memory.
 * Usage: $language = LangDetect::getInstance()->getBestMatch();
 *  * Usage: $language = LangDetect::getInstance()->getBestMatch('en', $langs);
 * ----------------------------------------------------------------------------
 * Created by Viacheslav Avramenko aka Lordz (avbitinfo@gmail.com)
 * Created on 09.11.2016. Last modified on 09.11.2016
 * ----------------------------------------------------------------------------
 * "THE BEER-WARE LICENSE":
 * As long as you retain this notice you can do whatever you want with this stuff.
 * If we meet some day, and you think this stuff is worth it, you can buy me a beer in return.
 * ----------------------------------------------------------------------------
 */

class LangDetect
{
    var $language = null;
    private static $_instance; // The single instance

    /**
     * Get an instance of the class
     * @return Instance
     */
    public static function getInstance() {
        if(!self::$_instance) {
            // If no instance then make one
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {
        if ($list = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE']) : null) {
            if (preg_match_all('/([a-z]{1,8}(?:-[a-z]{1,8})?)(?:;q=([0-9.]+))?/', $list, $list)) {
                $this->language = array_combine($list[1], $list[2]);
                foreach ($this->language as $n => $v)
                    $this->language[$n] = $v ? $v : 1;
                arsort($this->language, SORT_NUMERIC);
            }
        } else $this->language = array();
    }

    public function getBestMatch($default='en', $langs=null) {
        if (empty($default)) $default='en';
        if (empty($langs)) $langs = defined('LANGUAGE') ? unserialize(LANGUAGE) : array();

        $languages=array();
        foreach ($langs as $lang => $alias) {
            if (is_array($alias)) {
                foreach ($alias as $alias_lang) {
                    $languages[strtolower($alias_lang)] = strtolower($lang);
                }
            }else $languages[strtolower($alias)]=strtolower($lang);
        }
        foreach ($this->language as $l => $v) {
            $s = strtok($l, '-'); // remove what comes after the dash in the type of language "en-us, ru-ru"
            if (isset($languages[$s]))
                return $languages[$s];
        }
        return $default;
    }
}