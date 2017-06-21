<?php
/**
 * TWIG CUSTOM FILTERS
 * ----------------------------------------------------------------------------
 * Created by Viacheslav Avramenko aka Lordz (avbitinfo@gmail.com)
 * Created on 07.11.2016. Last modified on 07.11.2016
 * ----------------------------------------------------------------------------
 */

require_once __DIR__ . '/../../vendor/autoload.php';


$filter_sizeHR = new Twig_SimpleFilter('sizeHR', function ($size) {

    if ($size > 1099511627776) {
        return sprintf ("%01.2f TB", $size / 1099511627776); //$size / 1099511627776 . 'TB';
    } elseif ($size > 1073741824) {
        return sprintf ("%01.2f GB", $size / 1073741824); //$size / 1073741824 . 'GB';
    } elseif ($size > 1048576) {
        return sprintf ("%01.2f MB", $size / 1048576); //$size / 1048576 . 'MB';
    } elseif ($size > 1024){
        return sprintf ("%01.2f KB", $size / 1024);  //$size / 1024 . 'kB';
    }

    return $size . 'B';
});