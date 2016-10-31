<?php
/**
 * ----------------------------------------------------------------------------
 *                              SCRAPE
 * ----------------------------------------------------------------------------
 * Created by Viacheslav Avramenko aka Lordz (avbitinfo@gmail.com)
 * Created on 26.10.2016. Last modified on 27.10.2016
 * ----------------------------------------------------------------------------
 * "THE BEER-WARE LICENSE":
 * As long as you retain this notice you can do whatever you want with this stuff.
 * If we meet some day, and you think this stuff is worth it, you can buy me a beer in return.
 * ----------------------------------------------------------------------------
 */

header('Content-Type: text/plain; charset=UTF-8', true);

require_once '../app/autoload.php';


$info_hash = isset($_GET['info_hash']) ? $_GET['info_hash'] : '';

$torrent = array(
                'seeders' => 0,
                'leechers' => 0
                );

$empty = 'd5:filesd0:d8:completei0e10:incompletei0eeee';


if (strlen($info_hash) == 20) {
    $info_hash_hex = Database::getInstance()->real_escape_string(bin2hex($info_hash));
    $SQL = "SELECT seeders, leechers FROM torrent WHERE info_hash='$info_hash_hex' LIMIT 1;";
    if ($res = Database::getInstance()->query($SQL) ) {
        $row = $res->fetch_assoc();
        $torrent = array_merge($torrent, (array)$row);
        $res->close();
    }
} else {
    echo $empty;
    exit;
}

$output['files'][$info_hash] = array(
                                'complete' => (int)$torrent['seeders'],
                                'incomplete' => (int)$torrent['leechers']
                                );

echo bencode($output);

exit;


/*
if (strlen($info_hash) == 20) {
    $info_hash_hex = Database::getInstance()->real_escape_string(bin2hex($info_hash));
    if ($res = Database::getInstance()->query("SELECT seeder FROM announce WHERE info_hash_hex='$info_hash_hex';") ) {
        $seeders = $leechers = 0;
        while ($row = $res->fetch_assoc()){
            if ($row['seeder']=1) {
                $seeders++;
            } else {
                $leechers++;
            }
        }
        $res->close();

        $output['files'][$info_hash] = array(
            'complete' => $seeders,
            'incomplete' => $leechers,
        );

        echo bencode($output);
    }
} else {
    echo $empty;
}


exit;
*/