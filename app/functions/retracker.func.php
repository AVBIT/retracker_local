<?php
/**
 * RETRACKER FUNCTIONS
 * ----------------------------------------------------------------------------
 * Created by Viacheslav Avramenko aka Lordz (avbitinfo@gmail.com)
 * Created on 24.10.2016. Last modified on 06.09.2017
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

function encode_ip($dotquad_ip) {
    $ip_sep = explode('.', $dotquad_ip);
    if (count($ip_sep) == 4)
    {
        return sprintf('%02x%02x%02x%02x', $ip_sep[0], $ip_sep[1], $ip_sep[2], $ip_sep[3]);
    }

    $ip_sep = explode(':', preg_replace('/(^:)|(:$)/', '', $dotquad_ip));
    $res = '';
    foreach ($ip_sep as $x)
    {
        $res .= sprintf('%0' . ($x == '' ? (9 - count($ip_sep)) * 4 : 4) . 's', $x);
    }
    return $res;
}

function decode_ip ($ip) {
    //return long2ip("0x{$ip}");
	return long2ip(hexdec("0x{$ip}")); // PHP 7.1.0 - the parameter type of proper_address has been changed from string to integer ... string long2ip ( int $proper_address )
}

function verify_ip ($ip) {
    //return preg_match('#^(\d{1,3}\.){3}\d{1,3}$#', $ip);
    $iptype = false;
    if (strpos($ip, ':') !== false) {
        $iptype = 'ipv6';
    } elseif (preg_match('#^(\d{1,3}\.){3}\d{1,3}$#', $ip) !== false) {
        $iptype = 'ipv4';
    }
    return $iptype;
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
