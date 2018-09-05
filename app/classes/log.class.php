<?php
/**
 * LOG CLASS
 * ----------------------------------------------------------------------------
 * Log class using Singleton pattern.
 * Only one instance of the class will be made, this requires less memory.
 * Usage: Log::getInstance()->addWarning('Message');
 * ----------------------------------------------------------------------------
 * Created by Viacheslav Avramenko aka Lordz (avbitinfo@gmail.com)
 * Created on 27.11.2016. Last modified on 05.09.2018
 * ----------------------------------------------------------------------------
 * "THE BEER-WARE LICENSE":
 * As long as you retain this notice you can do whatever you want with this stuff.
 * If we meet some day, and you think this stuff is worth it, you can buy me a beer in return.
 * ----------------------------------------------------------------------------
 */


class Log {

    const TRACE = 1;
    const DEBUG = 2;
    const INFO  = 3;
    const WARNING = 4;
    const ERROR = 5;
    const FATAL = 6;

    const ROTATE_NEWER = 0;
    const ROTATE_HOURLY = 1;
    const ROTATE_DAILY  = 2;

    private $log_level;
    private $log_path;
    private $log_prefix_name;
    private $log_rotate;
    private $log_filename;


    private static $_instance; // The single instance

    /**
     * Get an instance of the class
    * @return Log
    */
    public static function getInstance() {
        if(!self::$_instance) {
            // If no instance then make one
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {

        if (!defined('LOG_LEVEL')) return;
        $this->log_level = defined('LOG_LEVEL') ? LOG_LEVEL : self::TRACE;
        $this->log_path = defined('LOG_PATH') ? LOG_PATH : (defined('DIR_LOG') ? DIR_LOG : sys_get_temp_dir());
        $this->log_prefix_name = defined('LOG_PREFIX_NAME') ? LOG_PREFIX_NAME : '';
        $this->log_rotate = defined('LOG_ROTATE') ? LOG_ROTATE : self::ROTATE_DAILY;

        if ($this->log_rotate == self::ROTATE_HOURLY){
            $this->log_filename = $this->log_path . $this->log_prefix_name . date('dh') . '.log';
            $filemtime = @filemtime($this->log_filename);  // returns FALSE if file does not exist
            if ($filemtime && (time() - $filemtime >= 3600)) @unlink($this->log_filename);
        } elseif ($this->log_rotate == self::ROTATE_DAILY){
            $this->log_filename = $this->log_path . $this->log_prefix_name . date('d') . '.log';
            $filemtime = @filemtime($this->log_filename);  // returns FALSE if file does not exist
            if ($filemtime && (time() - $filemtime >= 86400)) @unlink($this->log_filename);
        } else {
            if (empty($this->log_prefix_name)) $this->log_prefix_name = 'app';
            $this->log_filename = $this->log_path . $this->log_prefix_name . '.log';
        }

        //print "__construct(): " . __CLASS__ . ".class.php\n";
    }

    function __destruct() {
        //print "__destruct(): " . __CLASS__ . ".class.php\n";
    }

    // Magic method clone is empty to prevent duplication of connection
    private function __clone() { }

    public function addTrace($msg = null) {
        $this->add($msg, self::TRACE);
    }

    public function addDebug($msg = null) {
        $this->add($msg, self::DEBUG);
    }

    public function addInfo($msg = null) {
        $this->add($msg, self::INFO);
    }

    public function addWarning($msg = null) {
        $this->add($msg, self::WARNING);
    }

    public function addError($msg = null) {
        $this->add($msg, self::ERROR);
    }

    public function addFatal($msg = null) {
        $this->add($msg, self::FATAL);
    }

    private function add($msg, $level) {

        if (empty($msg) || $this->log_level > $level) return;

        $level_str = '';
        switch ($level) {
            case self::TRACE:
                $level_str = "[TRACE]";
                break;
            case self::DEBUG:
                $level_str = "[DEBUG]";
                break;
            case self::INFO:
                $level_str = "[INFO]";
                break;
            case self::WARNING:
                $level_str = "[WARNING]";
                break;
            case self::ERROR:
                $level_str = "[ERROR]";
                break;
            case self::FATAL:
                $level_str = "[FATAL]";
                break;
        }

        $str = sprintf("%s %s %s".PHP_EOL, date(DATE_RFC822), $level_str, $msg );
        @file_put_contents($this->log_filename, $str, FILE_APPEND);
    }

}
