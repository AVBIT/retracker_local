<?php
/**
 * ACCOUNT CLASS
 * ----------------------------------------------------------------------------
 * Account class using Singleton pattern.
 * Only one instance of the class will be made, this requires less memory.
 * Usage: $account = Account::getInstance();
 * ----------------------------------------------------------------------------
 * Created by Viacheslav Avramenko aka Lordz (avbitinfo@gmail.com)
 * Created on 28.10.2016. Last modified on 05.09.2018
 * ----------------------------------------------------------------------------
 * "THE BEER-WARE LICENSE":
 * As long as you retain this notice you can do whatever you want with this stuff.
 * If we meet some day, and you think this stuff is worth it, you can buy me a beer in return.
 * ----------------------------------------------------------------------------
 */


class Account {

    private static $_instance; // The single instance

    /**
    * Get an instance of the class
    * @return Account
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

    public function isAdm() {
        $result = false;
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
        if (!empty($ip)){
            $arr = preg_split("/[\s,;|]+/", ACCESS_ADMIN);
            //var_dump($arr);
            foreach ($arr as $elem){
                $result = $this->isIP4vsNET($ip,$elem);
                if ($result===true) break;
            }
        }
        return $result;
    }

    public function isAuth() {
        $result = false;
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
        if (!empty($ip)){
            $arr = preg_split("/[\s,;|]+/", ACCESS_TRUSTED_NETWORK);
            //var_dump($arr);
            foreach ($arr as $elem){
                $result = $this->isIP4vsNET($ip,$elem);
                if ($result===true) break;
            }
        }
        return $result;
    }


    public function get() {
        $account['local_ip'] = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
        $account['is_auth'] = $this->isAuth();
        $account['is_adm'] = $this->isAdm();

        return $account;
    }

    public function isIP4vsNET($ip, $network='10.0.0.0/8'){

        if (empty($ip) || empty($network)) return false;

        // test ip
        $o = explode('.',$ip);
        if (count($o)!=4) return false;
        foreach ($o as $item){
            if (!is_numeric($item)) return false;
            if ($item<0 || $item>255) return false;
        }

        // parse network/mask
        $mask = '255.255.255.255';
        $net_mask_arr = explode('/',$network);
        if (count($net_mask_arr)==2) {
            $network = $net_mask_arr[0];
            $mask = $net_mask_arr[1];
            if (is_numeric($mask)){
                $mask = long2ip(-1 << (32 - (int)$mask)); // INT to String;
            } else {
                // test mask
                $o = explode('.',$mask);
                if (count($o)!=4) return false;
                foreach ($o as $item){
                    if (!is_numeric($item)) return false;
                    if ($item<0 || $item>255) return false;
                }
            }
        }

        // test network
        $o = explode('.',$network);
        if (count($o)!=4) return false;
        foreach ($o as $item){
            if (!is_numeric($item)) return false;
            if ($item<0 || $item>255) return false;
        }

        if (((ip2long($ip))&(ip2long($mask)))==ip2long($network)) return true;
        return false;
    }


}
