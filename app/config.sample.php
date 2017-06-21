<?php
/**
 * CONFIG SAMPLE
 * ----------------------------------------------------------------------------
 * Created by Viacheslav Avramenko aka Lordz (avbitinfo@gmail.com)
 * Created on 02.03.2016. Last modified on 21.06.2017
 * ----------------------------------------------------------------------------
 * "THE BEER-WARE LICENSE":
 * As long as you retain this notice you can do whatever you want with this stuff.
 * If we meet some day, and you think this stuff is worth it, you can buy me a beer in return.
 * ----------------------------------------------------------------------------
 */

ini_set('display_errors', 0); // comment this line for development config (config.dev.php)

// TIMEZONE.
date_default_timezone_set('Europe/Kiev');

// DATABASE CONNECTION
define("DB_HOST",    'localhost'); // localhost - the fastest connection via UNIX socket!!!
define("DB_USER",    'your_db_user');
define("DB_PSWD",    'your_db_user_password');
define("DB_NAME",    'retracker');
define("DB_PORT",    3306);


define("DS",        DIRECTORY_SEPARATOR);
define('SELF',      substr(dirname(__FILE__),0,-3));

define('DIR_VAR', 	SELF."var".DS);
define('DIR_CACHE', DIR_VAR."cache".DS);
define('DIR_LOG',   DIR_VAR."log".DS);
define('DIR_TMP',   DIR_VAR."tmp".DS);

//LOG SETTINGS
define('LOG_LEVEL', 4);     // if (defined('LOG_LEVEL')) # Enable logging !!! (TRACE=1, DEBUG, INFO, WARNING, ERROR, FATAL=6)

// CLASS CACHE
define('CACHE', DIR_CACHE);         // if (defined('CACHE')) # Enable cache !!!
//define('CACHETYPE', "file");      // CACHETYPE value: file/redis ; default: file

// TWIG SETTINGS
define('TEMPLATES', SELF . "views" . DS);
define('TWIG_CACHE', DIR_CACHE);   // if (defined('TWIG_CACHE')) # Enable TWIG cache !!!

define('LANGUAGE', serialize(array(
    'en' => 'en',
    'uk' => 'uk',
    //'uk' => array('ru', 'uk'),
    'ru' => array('ru', 'be', 'ky', 'ab', 'mo', 'et', 'lv'),
)));


// Define access
define('ACCESS_TRUSTED_NETWORK', '127.0.0.1, 10.0.0.0/8');   //  separated string [\s,;|]
define('ACCESS_ADMIN', '127.0.0.1, 10.11.1.0/24'); // separated string [\s,;|]

define('OPEN_TRACKERS', 'udp://tracker.openbittorrent.com:80/announce, udp://tracker.opentrackr.org:1337/announce'); // separated string [\s,;|]


// TRACKER CONFIG
$tr_cfg = []; // array

// Garbage collector (run this script in cron each 5 minutes with '?run_gc=1' e.g. http://retracker.local/announce.php?run_gc=1)
$tr_cfg['run_gc_key'] = 'run_gc';

$tr_cfg['announce_interval']  = 120;        // sec, min = 60
$tr_cfg['peer_expire_factor'] = 3;        // min = 2; Consider a peer dead if it has not announced in a number of seconds equal
                                            //to this many times the calculated announce interval at the time of its last announcement
$tr_cfg['numwant']            = 50;         // number of peers being sent to client
$tr_cfg['ignore_reported_ip'] = true;       // Ignore IP reported by client
$tr_cfg['verify_reported_ip'] = false;      // Verify IP reported by client against $_SERVER['HTTP_X_FORWARDED_FOR']
$tr_cfg['allow_internal_ip']  = true;       // Allow internal IP (10.xx.. etc.)

$tr_cfg['compact_always'] = true;

