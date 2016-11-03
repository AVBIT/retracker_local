<?php
/**
 * ----------------------------------------------------------------------------
 *                              Cache CLASS
 * ----------------------------------------------------------------------------
 * Cache class using Singleton pattern.
 * Only one instance of the class will be made, this requires less memory.
 * Usage:
 * define('CACHE', 	SELF."cache".DS);   // if (defined('CACHE')) Enable cache !!!
 * define('CACHETYPE', "file");         // CACHETYPE value: file/redis ; default: file
 * $cache = @Cache::getInstance();
 * if (defined('CACHE')) $result = @Cache::getInstance()->set($key, $value, $ttl);
 * ----------------------------------------------------------------------------
 * Created by Viacheslav Avramenko aka Lordz (avbitinfo@gmail.com)
 * Created on 11.03.2016. Last modified on 03.11.2016
 * ----------------------------------------------------------------------------
 */

class Cache {
    private static $_instance; // The single instance
    private $cache;

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

    private function __construct() {

        $this->name = "CacheClass";

        if (defined('CACHE')) {
            if (!defined('CACHETYPE')) define('CACHETYPE', "file");

            $this->name .= "_".CACHETYPE;

            switch (CACHETYPE) {

                case 'file':
                    $this->cache = new cache_file();
                    break;
                case 'redis':
                    if (class_exists('Redis')) {
                        $this->cache = new cache_redis();
                        break;
                    }
                default:
                    $this->cache = new cache_common();
            }
        } else {
            $this->cache = new cache_common();
        }
    }

    //function __destruct() { //print "Destruct(): " . $this->name . "\n"; }

    /**
     * Returns value of variable
     */
    public function get ($key) {
        return $this->cache->get($key);
    }
    /**
     * Store value of variable
     */
    public function set ($key, $value, $ttl = 0) {
        return $this->cache->set($key, $value, $ttl);
    }
    /**
     * Remove variable
     */
    public function rm ($key) {
        return $this->cache->rm($key);
    }
    /**
     * Remove all variable (clear cache)
     */
    public function clear () {
        return $this->cache->clear();
    }

}


class cache_common
{
    /**
     * Returns value of variable
     */
    public function get ($key) {
        return false;
    }
    /**
     * Store value of variable
     */
    public function set ($key, $value, $ttl = 0) {
        return false;
    }
    /**
     * Remove variable
     */
    public function rm ($key) {
        return false;
    }
    /**
     * Remove all variable (clear cache)
     */
    public function clear () {
        
    }
}


class cache_file extends cache_common
{
    private $prefix = '';

    public function __construct() {

        if (defined('OPT_PREFIX')) $this->prefix = OPT_PREFIX . "_";
        if (mt_rand(1, 1000) <= 1) $this->gc(); // Garbage collection
    }

    /**
     * Returns value of variable
     */
    public function get ($key) {
        try {
            if (empty($key) ) throw new Exception("Empty 'name'!");
            $filename = $this->ValidateFileName($key);
            $result = @file_get_contents(CACHE.$filename);
            if ($result === false) throw new Exception("Can't read file '".CACHE.$filename."'!");
            //$result = unserialize($result);
            $result = json_decode($result, true, JSON_UNESCAPED_UNICODE);
            if (!empty($result)){ //is_array($result) // for unserialize !empty($result)
                if (isset($result['expiries']) && $result['expiries']<time()) {
                    $this->rm($key);
                    return NULL;
                }
                return $result['value'];
            }
        } catch (Exception $e) {
            //echo 'Exception: ',  $e->getMessage(), "\n";
        }
        return NULL;
    }

    /**
     * Store value of variable
     */
    public function set ($key, $value, $ttl = 0) {
        try {
            if (empty($key) || empty($value)) throw new Exception("Empty 'key' or 'value'!");
            $filename = $this->ValidateFileName($key);

            $arr['key'] = $key;
            if ($ttl>0) $arr['expiries'] = time()+$ttl;
            $arr['value'] = $value;     // may be error - JSON_ERROR_UTF8 - Malformed UTF-8 characters

            // Save array
            //$result = serialize($arr);
            $result = json_encode($arr, JSON_UNESCAPED_UNICODE );

            if ($result === false) {
                $msg = "Unknown error";
                switch (json_last_error()) {
                    case JSON_ERROR_NONE:
                        $msg = ' JSON_ERROR_NONE - No errors';
                        break;
                    case JSON_ERROR_DEPTH:
                        $msg = ' JSON_ERROR_DEPTH - Maximum stack depth exceeded';
                        break;
                    case JSON_ERROR_STATE_MISMATCH:
                        $msg = ' JSON_ERROR_STATE_MISMATCH - Underflow or the modes mismatch';
                        break;
                    case JSON_ERROR_CTRL_CHAR:
                        $msg = ' JSON_ERROR_CTRL_CHAR - Unexpected control character found';
                        break;
                    case JSON_ERROR_SYNTAX:
                        $msg = ' JSON_ERROR_SYNTAX - Syntax error, malformed JSON';
                        break;
                    case JSON_ERROR_UTF8:
                        $msg = ' JSON_ERROR_UTF8 - Malformed UTF-8 characters, possibly incorrectly encoded';
                        break;
                    default:
                        $msg = ' - Unknown error';
                        break;
                }
                throw new Exception("ERROR: json_encode($value). " . $msg);
            }
            if (file_put_contents(CACHE.$filename, $result) === false) throw new Exception("Can't write to file '".CACHE.$filename."'!");
            return true;

        } catch (Exception $e) {
            //echo 'Exception: ',  $e->getMessage(), "\n";
        }
        return false;
    }

    /**
     * Remove variable
     */
    public function rm ($key) {
        $filename = $this->ValidateFileName($key);
        if (@unlink(CACHE.$filename)=== true) return true;
        return false;
    }

    /**
     * Remove all variable (clear cache)
     */
    public function clear () {
        array_map('unlink', glob(CACHE."*.cache"));
        /*
        $files = scandir(CACHE);
        foreach ($files as $file){
            if (pathinfo($file, PATHINFO_EXTENSION) == 'cache') @unlink(CACHE.$file);
        }
        */
    }

    /**
     * Garbage collection
     */
    private function gc () {
        $utime = time();
        foreach (glob(CACHE."*.cache") as $filename) {
            if ( is_file($filename)) {
                $result = @file_get_contents($filename);
                $result = json_decode($result, true);
                if (!empty($result)){
                    if (isset($result['expiries']) && $result['expiries']<$utime) {
                        @unlink($filename);
                    }
                }
            }
        }
    }

    private function ValidateFileName($key){
        //return OPT_PREFIX . preg_replace('/[^a-zA-Z0-9_.-\/:]/', '_', basename($name)).'.cache';
        return  $this->prefix . preg_replace('/[^a-zA-Z0-9_.-]/', '_', basename($key)).'.cache';
    }
}


class cache_redis extends cache_common
{
    private $redis;
    private $redis_host = 'localhost';
    private $redis_port = 6379;
    private $redis_db = 1;
    private $redis_timeout = 3.5;
    private $redis_prefix = '';

    public function __construct() {

        if (defined('REDIS_HOST'))    $this->redis_host = REDIS_HOST;
        if (defined('REDIS_PORT'))    $this->redis_port = REDIS_PORT;
        if (defined('REDIS_DB'))      $this->redis_db = REDIS_DB;
        if (defined('REDIS_TIMEOUT')) $this->redis_timeout = REDIS_TIMEOUT;
        if (defined('OPT_PREFIX'))    $this->redis_prefix = OPT_PREFIX;

        try {
            $this->redis = new Redis();
            $this->redis->connect($this->redis_host, $this->redis_port, $this->redis_timeout);
        } catch (RedisException $e) {
            die("Redis connection ERROR!");
        }
        $this->redis->setOption(Redis::OPT_PREFIX, $this->redis_prefix);
        $this->redis->select($this->redis_db);
    }

    /**
     * Returns value of variable
     */
    function get ($key) {
        //if (preg_match('/^arr_/', $key)) return unserialize($this->redis->get($key));
        $value = $this->redis->get($key);
        if (($result = @unserialize($value)) === false) return $value;
        return $result;
    }

    /**
     * Store value of variable
     */
    function set ($key, $value, $ttl = 0) {
        if (is_array($value)) $value = serialize($value);
        if ($ttl==0) $this->redis->set($key, $value );
        return $this->redis->setex($key, $ttl, $value );
    }

    /**
     * Remove variable
     */
    function rm ($key) {
        return $this->redis->del($key);
    }

    /**
     * Remove all variable (clear cache)
     */
    public function clear () {
        //$prefix = $this->redis->getOptions()->__get('prefix')->getPrefix();
        foreach($this->redis->keys("*") as $key) {
            $this->redis->del($key);
        }
    }


}