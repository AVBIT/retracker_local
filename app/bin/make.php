<?php
/**
 * COPY some files from VENDOR to public WEB/ASSETS directory
 * ----------------------------------------------------------------------------
 * Created by Viacheslav Avramenko aka Lordz (avbitinfo@gmail.com)
 * Created on 21.06.2017. Last modified on 21.06.2017
 * ----------------------------------------------------------------------------
 */

require_once  __DIR__ . '/../autoload.php';

// Bootstrap
@mkdir(SELF.'web/assets/bootstrap');
recursive_copy ( SELF.'vendor/twbs/bootstrap/dist', SELF.'web/assets/bootstrap' );


// Clean cache
recursive_clean(DIR_CACHE);


function recursive_copy($src,$dst) {
    //@array_map('unlink', glob("$dst/*.*")); // delete all files
    // copy directory recursive
    $dir = opendir($src);
    @mkdir($dst);
    @file_put_contents($dst . DS . 'index.html', '');
    while(false !== ( $file = readdir($dir)) ) {
        if (( $file != '.' ) && ( $file != '..' )) {
            if ( is_dir($src . DS . $file) ) {
                recursive_copy($src . DS . $file,$dst . DS . $file);
            }
            else {
                copy($src . DS . $file,$dst . DS . $file);
            }
        }
    }
    closedir($dir);
}

function recursive_clean($dir) {
    //@array_map('unlink', glob("$dst/*.*")); // delete all files
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($files as $fileinfo) {
        $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
        if ($fileinfo->getFilename() !== '.gitignore'){
            $todo($fileinfo->getRealPath());
        }
    }
}