<?php
/**
 * ----------------------------------------------------------------------------
 *                              URI CLASS
 * ----------------------------------------------------------------------------
 * URI (Universal Resource Identifier) class using Singleton pattern.
 * Only one instance of the class will be made, this requires less memory.
 * Usage: $uri = Uri::getInstance();
 * ----------------------------------------------------------------------------
 * Created by Viacheslav Avramenko aka Lordz (avbitinfo@gmail.com)
 * Created on 15.11.2016. Last modified on 15.11.2016
 * ----------------------------------------------------------------------------
 * "THE BEER-WARE LICENSE":
 * As long as you retain this notice you can do whatever you want with this stuff.
 * If we meet some day, and you think this stuff is worth it, you can buy me a beer in return.
 * ----------------------------------------------------------------------------
 */


class Uri {

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
        //print "__construct(): " . __CLASS__ . ".class.php\n";
    }

    function __destruct() {
        //print "__destruct(): " . __CLASS__ . ".class.php\n";
    }

    public static function getMagnetURN($info_hash_hex, $name='', $size=0){
        $result = 'magnet:?xt=urn:btih:' . $info_hash_hex;
        $result .= !empty($name) ?  "&dn=$name" : '';
        $result .= !empty($name) ?  "&dl=$size" : '';
        $result .= '&tr=http://retracker.local/announce';
        return $result;
    }

    public static function makeURL($text = ''){
        return preg_replace("/[^\=\"]?(http:\/\/[a-zA-Z0-9\-.]+\.[a-zA-Z0-9\-]+([\/]([a-zA-Z0-9_\/\-.?&%=+])*)*)/", '<a href="$1" target="_blank">$1</a>', $text);
    }

    public static function getTrackerFromURL($text = ''){

    }


}
