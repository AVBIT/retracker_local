<?php
/**
 * ----------------------------------------------------------------------------
 *                              CONFIG
 * ----------------------------------------------------------------------------
 * Created by Viacheslav Avramenko aka Lordz (avbitinfo@gmail.com)
 * Created on 24.10.2016. Last modified on 24.10.2016
 * ----------------------------------------------------------------------------
 * "THE BEER-WARE LICENSE":
 * As long as you retain this notice you can do whatever you want with this stuff.
 * If we meet some day, and you think this stuff is worth it, you can buy me a beer in return.
 * ----------------------------------------------------------------------------
 */



function msg_die ($msg) {
    $output = bencode(array(
        'min interval'   => (int)    1800,
        'failure reason' => (string) $msg,
    ));
    die($output);
}

function encode_ip ($ip) {
    $d = explode('.', $ip);
    return sprintf('%02x%02x%02x%02x', $d[0], $d[1], $d[2], $d[3]);
}

function decode_ip ($ip) {
    return long2ip("0x{$ip}");
}

function verify_ip ($ip) {
    return preg_match('#^(\d{1,3}\.){3}\d{1,3}$#', $ip);
}

function str_compact ($str) {
    return preg_replace('#\s+#', ' ', trim($str));
}

// bencode: based on OpenTracker [http://whitsoftdev.com/opentracker]
function bencode ($var) {
    if (is_string($var)) 	{
        return strlen($var) .':'. $var;
    } else if (is_int($var)) 	{
        return 'i'. $var .'e';
    } else if (is_float($var)) {
        return 'i'. sprintf('%.0f', $var) .'e';
    } else if (is_array($var)) {

        if (count($var) == 0) {
            return 'de';
        } else {
            $assoc = false;
            foreach ($var as $key => $val) {
                if (!is_int($key)) {
                    $assoc = true;
                    break;
                }
            }

            if ($assoc) {
                ksort($var, SORT_REGULAR);
                $ret = 'd';
                foreach ($var as $key => $val) {
                    $ret .= bencode($key) . bencode($val);
                }
                return $ret .'e';
            } else {
                $ret = 'l';
                foreach ($var as $val) {
                    $ret .= bencode($val);
                }
                return $ret .'e';
            }
        }
    } else {
        trigger_error('bencode error: wrong data type', E_USER_ERROR);
    }
}
