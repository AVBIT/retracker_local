<?php
/**
 * ----------------------------------------------------------------------------
 *                              URI CLASS
 * ----------------------------------------------------------------------------
 * URI (Universal Resource Identifier) class.
 * Usage: $url = Uri::makeURL('http://example.com');
 * ----------------------------------------------------------------------------
 * Created by Viacheslav Avramenko aka Lordz (avbitinfo@gmail.com)
 * Created on 15.11.2016. Last modified on 16.11.2016
 * ----------------------------------------------------------------------------
 * "THE BEER-WARE LICENSE":
 * As long as you retain this notice you can do whatever you want with this stuff.
 * If we meet some day, and you think this stuff is worth it, you can buy me a beer in return.
 * ----------------------------------------------------------------------------
 */


class Uri {

    public static function makeMagnetURN($info_hash_hex, $name='', $size=0, $comment=''){
        $result = 'magnet:?xt=urn:btih:' . $info_hash_hex;
        $result .= !empty($name) ?  "&dn=$name" : '';
        $result .= !empty($name) ?  "&dl=$size" : '';
        //if (!empty($comment)) $result .= '&tr=' . Uri::getTrackerFromCommentURL($comment); // Don't work :(
        $result .= '&tr=http://retracker.local/announce';
        return $result;
    }

    public static function makeURL($text = ''){
        return preg_replace("/[^\=\"]?(http:\/\/[a-zA-Z0-9\-.]+\.[a-zA-Z0-9\-]+([\/]([a-zA-Z0-9_\/\-.?&%=+])*)*)/", '<a href="$1" target="_blank">$1</a>', $text);
    }

    /*
    public static function getTrackerFromCommentURL($text = ''){ // Can not resolve announcer URL! Don't work :(
        $result = '';
        $parseUrl = parse_url(trim($text));
        if ($parseUrl !== false){
            if (isset($parseUrl['scheme']) && isset($parseUrl['host'])){
                $result = $parseUrl['scheme'] . '://' . $parseUrl['host'] . '/announce';
            }
        }

        return $result;
    }
    */

}
