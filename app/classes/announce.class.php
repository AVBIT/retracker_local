<?php
/**
 * ----------------------------------------------------------------------------
 *                              ANNOUNCE CLASS
 * ----------------------------------------------------------------------------
 * Announce class using Singleton pattern.
 * Only one instance of the class will be made, this requires less memory.
 * Usage: $announce = Announce::getInstance();
 * ----------------------------------------------------------------------------
 * Created by Viacheslav Avramenko aka Lordz (avbitinfo@gmail.com)
 * Created on 27.03.2016. Last modified on 14.11.2016
 * ----------------------------------------------------------------------------
 * "THE BEER-WARE LICENSE":
 * As long as you retain this notice you can do whatever you want with this stuff.
 * If we meet some day, and you think this stuff is worth it, you can buy me a beer in return.
 * ----------------------------------------------------------------------------
 */


class Announce {

    private static $_instance; // The single instance

    private $announce = [];


    /**
    * Get an instance of the class
    * @return Instance
    */
    public static function getInstance() {
        if(!self::$_instance) {
            // If no instance then make one
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct() {

        $this->tablename = 'announce';
        $this->db = Database::getInstance();

        // Input var names
        // String
        $input_vars_str = array(
            'info_hash',
            'peer_id',
            'ipv4',
            'ipv6',
            'event',
            'name',
            'comment'
        );
        // Numeric
        $input_vars_num = array(
            'port',
            'numwant',
            'left',
            'compact',
            'size'
        );

        $this->announce['peer_id'] = '';
        $this->announce['ip'] = '';
        $this->announce['ipv6'] = '';
        $this->announce['port'] = 0;
        $this->announce['seeder'] = 0;
        $this->announce['info_hash'] = '';
        $this->announce['event'] = '';
        $this->announce['name'] = '';
        $this->announce['comment'] = '';
        $this->announce['info_hash_hex'] = '';
        $this->announce['peer_hash'] = '';
        $this->announce['update_time'] = 0;
    }

    function __destruct() {
        //print "__destruct(): " . __CLASS__ . ".class.php\n";
    }

    public function set($arr) {
        $result = $this->announce;
        if (empty($arr)) return $result;
        foreach ($this->announce as $key => $value){
            //$this->announce[$key] = isset($arr[$key]) ? $this->announce['comment']
        }
        return $result;
    }

    public function getHumanReadable($page = 1, $row_in_page = 20){

        if ((int)$row_in_page < 10) $row_in_page = 10;
        if ((int)$page < 1) $page = 1;
        $offset = (int)$page*$row_in_page;

        // Read from cache
        $cache_key = 'Announce_p'.$page;
        if (defined('CACHE')){
            $result=@Cache::getInstance()->get( $cache_key );
            //var_dump($result);
            if (!empty($result)) return $result;
        }


        $SQL = "SELECT                   
                  info_hash_hex,
                  seeder,
                  `name`,
                  `size`,
                  `comment`,
				  update_time 
				FROM $this->tablename ORDER BY update_time DESC;";

        $arr=[];
        if ($res = $this->db->query($SQL) ){
            while ($row = $res->fetch_assoc()){
                $info_hash_hex = $row['info_hash_hex'];
                if (!isset($arr[$info_hash_hex])){
                    $arr[$info_hash_hex]['info_hash_hex'] = $info_hash_hex;
                    $arr[$info_hash_hex]['seeders'] = 0;
                    $arr[$info_hash_hex]['leechers'] = 0;
                }

                if (isset($row['seeder']) && !empty($row['seeder'])) {
                    $arr[$info_hash_hex]['seeders']++;
                } else {
                    $arr[$info_hash_hex]['leechers']++;
                }
                $arr[$info_hash_hex]['name'] = !empty($row['name']) ? mb_convert_encoding($row['name'], "UTF-8", "CP1251") : '';
                $arr[$info_hash_hex]['size'] = $row['size'];
                $arr[$info_hash_hex]['comment'] = !empty($row['comment']) ? mb_convert_encoding($row['comment'], "UTF-8", "CP1251") : '';
                $arr[$info_hash_hex]['update_time'] = $row['update_time'];

                if (isset($arr[$info_hash_hex]['comment'])) {
                    // if URL... create hyperlink
                    $arr[$info_hash_hex]['comment'] = preg_replace("/[^\=\"]?(http:\/\/[a-zA-Z0-9\-.]+\.[a-zA-Z0-9\-]+([\/]([a-zA-Z0-9_\/\-.?&%=+])*)*)/", '<a href="$1">$1</a>', $arr[$info_hash_hex]['comment']);
                }
            }
            $res->close();
        }

        // delete anonymous announcements
        foreach ($arr as $key=>$value){
            if (empty($arr[$key]['name']) && empty($arr[$key]['comment'])) unset($arr[$key]);
        }

        $result['page_num'] = (int)$page;
        $result['pages'] = ceil(count($arr) / (int)$row_in_page)-1;
        if ($result['page_num']>$result['pages']) $this->getHumanReadable(1,$row_in_page);
        $result['result'] = array_slice($arr, $offset, $offset+$row_in_page);

        // Save to cache
        if (defined('CACHE')) @Cache::getInstance()->set( $cache_key , $result, 10);

        return $result;
    }

    private function actionCompleted ($arr){

    }
    private function actionStarted  ($arr){

    }
    private function actionStopped ($arr){

    }

    public function getScrapeByInfoHash ($info_hash = null){
        $result = 'd5:filesd0:d8:completei0e10:incompletei0eeee'; // empty result
        if (empty($info_hash) || strlen($info_hash) != 20) return $result;
        $info_hash = @mb_convert_encoding($info_hash, "UTF-8", "auto");
        $info_hash_hex = bin2hex($info_hash);
        $info_hash_hex_sql = Database::getInstance()->real_escape_string($info_hash_hex);
        //echo $info_hash_hex;

        // Read from cache
        if (defined('CACHE')){
            $result=@Cache::getInstance()->get('scrape_' . $info_hash_hex);
            if (!empty($result)) return $result;
        }

        $seeders = $leechers = 0;
        if ($res = Database::getInstance()->query("SELECT seeder FROM $this->tablename WHERE info_hash_hex='$info_hash_hex_sql';") ) {
            while ($row = $res->fetch_assoc()){
                if ($row['seeder']=1) {
                    $seeders++;
                } else {
                    $leechers++;
                }
            }
            $res->close();
        }
        $output['files'][$info_hash] = array(
                                            'complete' => $seeders,
                                            'incomplete' => $leechers,
                                            );

        $result = bencode($output);

        // Save to cache
        if (defined('CACHE')) @Cache::getInstance()->set('scrape_' . $info_hash_hex, $result, 60);

        return $result;
    }
}
