<?php
/**
 * ----------------------------------------------------------------------------
 *                              CONFIG
 * ----------------------------------------------------------------------------
 * Modified by Viacheslav Avramenko aka Lordz (avbitinfo@gmail.com)
 * Created on 02.03.2016. Last modified on 06.03.2016
 * ----------------------------------------------------------------------------
 * "THE BEER-WARE LICENSE":
 * As long as you retain this notice you can do whatever you want with this stuff.
 * If we meet some day, and you think this stuff is worth it, you can buy me a beer in return.
 * ----------------------------------------------------------------------------
 */


// DATABASE CONNECTION
define("DB_HOST",    'localhost'); // Localhost via UNIX socket!!!
define("DB_USER",    'root');
define("DB_PSWD",    '');
define("DB_NAME",    'retracker');
define("DB_PORT",    3306);

//define("DS",        DIRECTORY_SEPARATOR);
//define('SELF',      substr(dirname(__FILE__),0,-3));

//define('INC',       dirname(__FILE__));
//define('CLASSES',   dirname(__FILE__).DS."classes".DS);

//define('CACHE', 	NULL);              // Disable cache !!!
//define('CACHE', 	SELF."cache".DS);   // Enable cache !!!
//define('TEMPLATES', SELF."tmpl".DS);


// TRACKER CONFIG
$tr_cfg = []; // array

// Garbage collector (run this script in cron each 5 minutes with '?run_gc=1' e.g. http://retracker.local/announce.php?run_gc=1)
$tr_cfg['run_gc_key'] = 'run_gc';

$tr_cfg['announce_interval']  = 1800;       // sec, min = 60
$tr_cfg['peer_expire_factor'] = 2.5;        // min = 2; Consider a peer dead if it has not announced in a number of seconds equal
                                            //to this many times the calculated announce interval at the time of its last announcement
$tr_cfg['numwant']            = 50;         // number of peers being sent to client
$tr_cfg['ignore_reported_ip'] = true;       // Ignore IP reported by client
$tr_cfg['verify_reported_ip'] = false;      // Verify IP reported by client against $_SERVER['HTTP_X_FORWARDED_FOR']
$tr_cfg['allow_internal_ip']  = true;       // Allow internal IP (10.xx.. etc.)


?>
