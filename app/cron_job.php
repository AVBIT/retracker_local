<?php
/**
 * ----------------------------------------------------------------------------
 *                              CRON JOB
 * ----------------------------------------------------------------------------
 * Created by Viacheslav Avramenko aka Lordz (avbitinfo@gmail.com)
 * Created on 27.10.2016. Last modified on 28.10.2016
 * ----------------------------------------------------------------------------
 * "THE BEER-WARE LICENSE":
 * As long as you retain this notice you can do whatever you want with this stuff.
 * If we meet some day, and you think this stuff is worth it, you can buy me a beer in return.
 * ----------------------------------------------------------------------------
 */

require_once 'autoload.php';


// Garbage collector
//file_get_contents("http://retracker.local/announce.php?" . $tr_cfg['run_gc_key'] . "=1");

$announce_interval = max(intval($tr_cfg['announce_interval']), 60);
$expire_factor     = max(floatval($tr_cfg['peer_expire_factor']), 2);
$peer_expire_time  = time() - floor($announce_interval * $expire_factor);

$SQL = '';
$SQL .= "DELETE FROM announce WHERE update_time < $peer_expire_time;";
$SQL .= "DELETE FROM torrent WHERE torrent_id NOT IN (SELECT DISTINCT(torrent_id) FROM announce) AND ( (`name` IS NULL OR `name` = '') AND (`comment` IS NULL OR `comment` = '') );";
$SQL .= "UPDATE torrent SET seeders=0, leechers=0 WHERE torrent_id NOT IN (SELECT DISTINCT(torrent_id) FROM announce);";

Database::getInstance()->query($SQL);