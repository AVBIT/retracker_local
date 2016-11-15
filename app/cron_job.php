<?php
/**
 * ----------------------------------------------------------------------------
 *                              CRON JOB
 * ----------------------------------------------------------------------------
 * Created by Viacheslav Avramenko aka Lordz (avbitinfo@gmail.com)
 * Created on 27.10.2016. Last modified on 14.11.2016
 * ----------------------------------------------------------------------------
 * "THE BEER-WARE LICENSE":
 * As long as you retain this notice you can do whatever you want with this stuff.
 * If we meet some day, and you think this stuff is worth it, you can buy me a beer in return.
 * ----------------------------------------------------------------------------
 */

require_once 'autoload.php';


// Garbage collector
file_get_contents("http://retracker.local/announce.php?" . $tr_cfg['run_gc_key'] . "=1");

/*
$announce_interval = max(intval($tr_cfg['announce_interval']), 60);
$expire_factor     = max(floatval($tr_cfg['peer_expire_factor']), 2);
//$peer_expire_time  = time() - floor($announce_interval * $expire_factor);
$diff_time  = floor($announce_interval * $expire_factor);

$db = Database::getInstance();
$db->query("DELETE LOW_PRIORITY FROM announce WHERE update_time < (UNIX_TIMESTAMP()-$diff_time);");
$db->query("UPDATE LOW_PRIORITY bittorrent SET seeders=0, leechers=0 WHERE update_time < (UNIX_TIMESTAMP()-$diff_time) AND (seeders!=0 OR leechers!=0);");
*/
exit;
