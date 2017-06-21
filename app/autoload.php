<?php
/**
 * ----------------------------------------------------------------------------
 *                          AUTOLOAD APPLICATION
 * ----------------------------------------------------------------------------
 * Created by Viacheslav Avramenko aka Lordz (avbitinfo@gmail.com)
 * Created on 21.10.2016. Last modified on 24.11.2016
 * ----------------------------------------------------------------------------
 * "THE BEER-WARE LICENSE":
 * As long as you retain this notice you can do whatever you want with this stuff.
 * If we meet some day, and you think this stuff is worth it, you can buy me a beer in return.
 * ----------------------------------------------------------------------------
 */


// Include config
if (isset($_SERVER['REMOTE_ADDR']) && ($_SERVER['REMOTE_ADDR']=='::1' || $_SERVER['REMOTE_ADDR']=='127.0.0.1') && file_exists(__DIR__.'/config.local.php') ){
    require_once 'config.dev.php'; // may be local config (for )
} else {
    require_once 'config.prod.php';
}


// Register my classes
spl_autoload_register(function ($class) {
    require_once __DIR__ . '/classes/' . strtolower($class) . '.class.php';
});


// Register my functions
require_once __DIR__ . '/functions/retracker.func.php';
require_once __DIR__ . '/functions/twigfilters.func.php';


// Register other (vendor) classes
require_once __DIR__ . '/../vendor/autoload.php';