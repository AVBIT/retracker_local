<?php
/**
 * ----------------------------------------------------------------------------
 *                          AUTOLOAD APPLICATION
 * ----------------------------------------------------------------------------
 * Created by Viacheslav Avramenko aka Lordz (avbitinfo@gmail.com)
 * Created on 21.10.2016. Last modified on 24.10.2016
 * ----------------------------------------------------------------------------
 * "THE BEER-WARE LICENSE":
 * As long as you retain this notice you can do whatever you want with this stuff.
 * If we meet some day, and you think this stuff is worth it, you can buy me a beer in return.
 * ----------------------------------------------------------------------------
 */


// Include config
if ($_SERVER['REMOTE_ADDR']=='::1' || $_SERVER['REMOTE_ADDR']=='127.0.0.1' ){
    require_once 'config.local.php'; // may be local config
} else {
    require_once 'config.inc.php';
}


// Register my classes
spl_autoload_register(function ($class) {
    require_once __DIR__ . '/classes/' . strtolower($class) . '.class.php';
});


// Register my functions
require_once __DIR__ . '/functions/retracker.func.php';


// Register other (vendor) classes
//require_once __DIR__ . '/../vendor/autoload.php';