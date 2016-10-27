<?php
/**
 * ----------------------------------------------------------------------------
 *                              CRON JOB
 * ----------------------------------------------------------------------------
 * Created by Viacheslav Avramenko aka Lordz (avbitinfo@gmail.com)
 * Created on 27.10.2016. Last modified on 27.10.2016
 * ----------------------------------------------------------------------------
 * "THE BEER-WARE LICENSE":
 * As long as you retain this notice you can do whatever you want with this stuff.
 * If we meet some day, and you think this stuff is worth it, you can buy me a beer in return.
 * ----------------------------------------------------------------------------
 */

require_once 'autoload.php';


// Garbage collector
file_get_contents("http://retracker.local/announce.php?" . $tr_cfg['run_gc_key'] . "=1");
