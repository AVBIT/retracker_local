<?php
/**
 * SCRAPE
 * ----------------------------------------------------------------------------
 * Created by Viacheslav Avramenko aka Lordz (avbitinfo@gmail.com)
 * Created on 26.10.2016. Last modified on 11.11.2016
 * ----------------------------------------------------------------------------
 * "THE BEER-WARE LICENSE":
 * As long as you retain this notice you can do whatever you want with this stuff.
 * If we meet some day, and you think this stuff is worth it, you can buy me a beer in return.
 * ----------------------------------------------------------------------------
 */

header('Content-Type: text/plain; charset=UTF-8', true);

require_once '../app/autoload.php';


echo Announce::getInstance()->getScrapeByInfoHash(isset($_GET['info_hash']) ? $_GET['info_hash'] : null);

exit;