<?php
/**
 * ----------------------------------------------------------------------------
 *                              DATABASE CLASS
 * ----------------------------------------------------------------------------
 * Database class using MySQLI and Singleton pattern.
 * Only one instance of the class will be made, this requires less memory.
 * Usage: $db = Database::getInstance();
 * ----------------------------------------------------------------------------
 * Created by Viacheslav Avramenko aka Lordz (avbitinfo@gmail.com)
 * Created on 06.03.2016. Last modified on 26.10.2016
 * ----------------------------------------------------------------------------
 * "THE BEER-WARE LICENSE":
 * As long as you retain this notice you can do whatever you want with this stuff.
 * If we meet some day, and you think this stuff is worth it, you can buy me a beer in return.
 * ----------------------------------------------------------------------------
 */


class Database extends mysqli {

    private static $_instance; // The single instance

    /**
    * Get an instance of the Database
    * @return Instance
    */
    public static function getInstance() {
        if(!self::$_instance) {
            // If no instance then make one
            self::$_instance = new self();
        }
        return self::$_instance;
    }
	
	// Redeclare constructor
    public function __construct($host=DB_HOST, $user=DB_USER, $password=DB_PSWD, $database=DB_NAME, $port=DB_PORT, $socket=NULL, $flags=0) {
        parent::init();

        if (!parent::options(MYSQLI_INIT_COMMAND, 'SET CHARACTER SET \'cp1251\'')) {
            die('Set MYSQLI_INIT_COMMAND \'SET CHARACTER SET \'cp1251\'\' ended in failure!');
        }

        if (!parent::options(MYSQLI_INIT_COMMAND, 'SET AUTOCOMMIT = 1')) {
            die('Set MYSQLI_INIT_COMMAND \'SET AUTOCOMMIT = 1\' ended in failure!');
        }

        if (!parent::options(MYSQLI_OPT_CONNECT_TIMEOUT, 5)) {
            die('Set MYSQLI_OPT_CONNECT_TIMEOUT ended in failure!');
        }

        if (!parent::real_connect($host, $user, $password, $database, $port, $socket, $flags)) {
            //die('Connection error (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
        }
    }

    // Magic method clone is empty to prevent duplication of connection
    private function __clone() { }

}
