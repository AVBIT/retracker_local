<?php
/**
 * CRON JOB
 * ----------------------------------------------------------------------------
 * Created by Viacheslav Avramenko aka Lordz (avbitinfo@gmail.com)
 * Created on 27.10.2016. Last modified on 21.06.2017
 * ----------------------------------------------------------------------------
 * "THE BEER-WARE LICENSE":
 * As long as you retain this notice you can do whatever you want with this stuff.
 * If we meet some day, and you think this stuff is worth it, you can buy me a beer in return.
 * ----------------------------------------------------------------------------
 */


require_once  '../autoload.php';

$time_start = microtime(true);
$semafor_file = DIR_TMP . "cron_job_isrun.semafor";

if (file_exists($semafor_file)) {
    $diff = time() - filemtime($semafor_file);
    if ($diff < 1800) {
        Log::getInstance()->addWarning('CRONJOB: It is not possible to process the cron job as the previous job is still running!');
        exit;
    }
}
file_put_contents($semafor_file, date('D M d H:i:s T Y'));



// PROCESSING CRON JOB
file_get_contents("http://retracker.local/announce.php?" . $tr_cfg['run_gc_key'] . "=1"); // Garbage collector
Resolver::getInstance()->resolveAllAnnounces();     // Resolve unknown name and size announces
Announce::getInstance()->SaveAllToHistory();        // Save(update) resolved announces



if (defined('LOG_LEVEL') && LOG_LEVEL<=Log::INFO){
    $msg = __METHOD__ . sprintf("CRONJOB: EXECUTIONTIME: %.5F sec.", microtime(true) - $time_start);
    Log::getInstance()->addInfo($msg);
}

unlink($semafor_file);
exit;