<?php
/**
 * ----------------------------------------------------------------------------
 *                              ACCOUNT CLASS
 * ----------------------------------------------------------------------------
 * Account class using Singleton pattern.
 * Only one instance of the class will be made, this requires less memory.
 * Usage: $account = Account::getInstance();
 * ----------------------------------------------------------------------------
 * Created by Viacheslav Avramenko aka Lordz (avbitinfo@gmail.com)
 * Created on 28.10.2016. Last modified on 01.11.2016
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

    public function isAuth() {
        $result = false;
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
        if (!empty($ip)){
            $result = $this->isIPvsNET($ip);
            if ($ip=='::1' || $ip=='127.0.0.1') $result = true;
        }
        return $result;
    }

    public function isAdm() {
        $result = false;
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
        if (!empty($ip)){
            $result = $this->isIPvsNET($ip,'10.11.45.0', '255.255.255.0');
            if ($ip=='::1' || $ip=='127.0.0.1') $result = true;
        }
        return $result;
    }

    public function get() {
        $account['local_ip'] = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
        $account['is_auth'] = $this->isAuth();
        $account['is_adm'] = $this->isAdm();

        return $account;
    }

    private function isIPvsNET($ip,$network='10.0.0.0',$mask='255.0.0.0'){
        if (((ip2long($ip))&(ip2long($mask)))==ip2long($network)) return true;
        return false;
    }


}
