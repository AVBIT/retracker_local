<?php
/**
 * ANNOUNCE CLASS
 * ----------------------------------------------------------------------------
 * Announce class using Singleton pattern.
 * Only one instance of the class will be made, this requires less memory.
 * Usage: $announce = Announce::getInstance();
 * ----------------------------------------------------------------------------
 * Created by Viacheslav Avramenko aka Lordz (avbitinfo@gmail.com)
 * Created on 27.03.2016. Last modified on 21.06.2017
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

    public function Save($info_hash_hex,$name='',$size=0,$comment='',$seeders=0,$leechers=0){

        try {
            if (empty($info_hash_hex)) return;
            $info_hash_hex = $this->db->real_escape_string($info_hash_hex);
            $name = $this->db->real_escape_string($name);
            $comment = $this->db->real_escape_string($comment);
            $size = is_numeric($size) ? (int)$size : 0;
            $seeders = is_numeric($seeders) ? (int)$seeders : 0;
            $leechers = is_numeric($leechers) ? (int)$leechers : 0;

            $SQL = "INSERT DELAYED INTO announce_resolver
				(info_hash_hex, seeders, leechers, `name`, `size`, `comment`, update_time)
			VALUES
				('$info_hash_hex', '$seeders', '$leechers', '$name', '$size', '$comment', UNIX_TIMESTAMP())
			ON DUPLICATE KEY UPDATE seeders = '$seeders', leechers = '$leechers', update_time = UNIX_TIMESTAMP();
			";

            if ($this->db->query($SQL) === false){
                throw new Exception(__METHOD__ . PHP_EOL . $SQL . PHP_EOL. $this->db->error );
            }

        } catch (Throwable $t) {
            // Executed only in PHP 7, will not match in PHP 5.x
            Log::getInstance()->addError($t->getMessage());
        } catch (Exception $e) {
            // Executed only in PHP 5.x, will not be reached in PHP 7
            Log::getInstance()->addError($e->getMessage());
        }

        // test!!! Put to cron job!!!
        //if (mt_rand(1, 10000) <= 1) $this->SaveAllToHistory();
    }

    public function SaveAllToHistory(){

        $SQL = "SELECT info_hash_hex, `name`, `size`, `comment`, update_time 
                FROM announce_resolver
                WHERE `name`!='' AND `size`>0;
			    ";
        if ($res = $this->db->query($SQL) ){
            while ($row = $res->fetch_assoc()){
                History::getInstance()->Save($row['info_hash_hex'],$row['name'],(int)$row['size'],$row['comment'],$row['update_time']);
            }
            $res->close();
        }

    }

    public function getPageOfList($page = 1, $row_in_page = 50){

        if ((int)$row_in_page < 10) $row_in_page = 10;
        if ((int)$page < 1) $page = 1;
        $offset = (int)$page*$row_in_page-$row_in_page;

        // Read from cache
        $cache_key = 'announce_p'.$page;
        if (defined('CACHE')){
            $cache_result=@Cache::getInstance()->get( $cache_key );
            if (!empty($cache_result)) return $cache_result;
        }

        $SQL = "SELECT                   
                  info_hash_hex,
                  seeders,
                  leechers,
                  `name`,
                  `size`,
                  `comment`,
				  update_time 
				FROM announce_resolver 
				WHERE `name`!='' AND `size`>0
				ORDER BY update_time DESC;";

        $arr=[];
        if ($res = $this->db->query($SQL) ){
            while ($row = $res->fetch_assoc()){

                $row['name'] = !empty($row['name']) ? mb_convert_encoding($row['name'], "UTF-8", "CP1251") : '';
                $row['comment'] = !empty($row['comment']) ? mb_convert_encoding($row['comment'], "UTF-8", "CP1251") : '';

                // create URL and URN
                $row['magnet_urn'] = Uri::makeMagnetURN($row['info_hash_hex'],$row['name'],$row['size'],$row['comment']);
                $row['comment'] = Uri::makeURL($row['comment']);
                $arr[] = $row;
            }
            $res->close();
        }

        $result['page_num'] = (int)$page;
        $result['pages'] = floor(count($arr) / (int)$row_in_page)+1;
        if ($result['page_num']>$result['pages']) $this->getHumanReadable(1,$row_in_page);
        $result['result'] = array_slice($arr, $offset, $row_in_page);

        // Save to cache
        if (defined('CACHE')) @Cache::getInstance()->set( $cache_key , $result, 10);

        return $result;
    }


    public function getStatistic (){

        $result['count_peers'] = 0;
        $result['count_seeders'] = 0;
        $result['count_leechers'] = 0;

        $result['count_info_hash_announce'] = 0;
        $result['count_info_hash_history'] = 0;

        $result['count_resolved_info_hash'] = 0;
        $result['count_unknoun_info_hash'] = 0;

        $result['request_per_second']['max'] = 0;
        $result['request_per_second']['last'] = 0;
        $result['request_per_second']['avg'] = 0;

        $request = [];

        $SQL = "SELECT seeders, leechers, `name`, UNIX_TIMESTAMP()-update_time as diff_time FROM announce_resolver;";

        if ($res = $this->db->query($SQL) ) {
            while ($row = $res->fetch_assoc()) {
                $result['count_seeders'] += $row['seeders'];
                $result['count_leechers'] += $row['leechers'];

                if ($row['diff_time']>0 && $row['diff_time']<=60){
                    if (!isset($request[$row['diff_time']])) $request[$row['diff_time']] = 0; // init array key
                    $request[$row['diff_time']]++;
                }

                $result['count_info_hash_announce']++;
                if (!empty($row['name'])){
                    $result['count_resolved_info_hash']++;
                } else {
                    $result['count_unknoun_info_hash']++;
                }
            }
            $res->close();
        }
        $result['count_peers'] = $result['count_seeders'] + $result['count_leechers'];
        $result['count_info_hash_history'] = History::getInstance()->getTableRecordsCount();

        if (!empty($request)){
            $result['request_per_second']['max'] = max($request);
            $result['request_per_second']['last'] = isset($request[1])? $request[1] : 0;
            $result['request_per_second']['avg'] = ceil(array_sum($request)/count($request));
        }

        return $result;
    }

    /*
    private function actionCompleted ($arr){

    }
    private function actionStarted  ($arr){

    }
    private function actionStopped ($arr){

    }
    */

    public function getScrapeByInfoHash ($info_hash = null){
        $result = 'd5:filesd0:d8:completei0e10:incompletei0eeee'; // empty result
        if (empty($info_hash) || strlen($info_hash) != 20) return $result;
        $info_hash = @mb_convert_encoding($info_hash, "UTF-8", "auto");
        $info_hash_hex = bin2hex($info_hash);
        $info_hash_hex_sql = Database::getInstance()->real_escape_string($info_hash_hex);
        //echo $info_hash_hex;

        // Read from cache
        if (defined('CACHE')){
            $cache_result=@Cache::getInstance()->get('scrape_' . $info_hash_hex);
            if (!empty($cache_result)) return $cache_result;
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
