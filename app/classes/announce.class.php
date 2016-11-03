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
 * Created on 27.03.2016. Last modified on 03.11.2016
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

    public function getHumanReadable(){
        $result = [];

        // Read from cache
        if (defined('CACHE')){
            $result=@Cache::getInstance()->get( __FUNCTION__ . __CLASS__ );
            //var_dump($result);
            if (!empty($result)) return $result;
        }

        $SQL = "SELECT
                  torrent.info_hash_hex,
                  torrent.seeders,
                  torrent.leechers,
                  torrent.reg_time,
                  torrent.update_time,
                  torrent.`name`,
                  torrent.size,
                  torrent.`comment`
                FROM
                  announce
                LEFT JOIN torrent ON torrent.torrent_id = announce.torrent_id
                WHERE NOT ISNULL(`name`) AND `name` != ''
                GROUP BY info_hash_hex
                ORDER by update_time;
                ";

        //echo $SQL; // test only
        if ($res = $this->db->query($SQL) ){
            while ($row = $res->fetch_assoc()){
                if (isset($row['name'])) $row['name'] = mb_convert_encoding($row['name'], "UTF-8", "CP1251");
                $result[] = $row;
            }
            $res->close();
        }

        // Save to cache
        if (defined('CACHE')) @Cache::getInstance()->set( __FUNCTION__ . __CLASS__ , $result, 1200);

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
        $info_hash = mb_convert_encoding($info_hash, "UTF-8", "auto");
        $info_hash_hex = bin2hex($info_hash);
        //echo $info_hash_hex;

        // Read from cache
        if (defined('CACHE')){
            $result=@Cache::getInstance()->get('scrape_' . $info_hash_hex); // __FUNCTION__ . bin2hex($info_hash)
            if (!empty($result)) return $result;
        }

        $info_hash_hex = Database::getInstance()->real_escape_string($info_hash_hex);

        $seeders = $leechers = 0;
        if ($res = Database::getInstance()->query("SELECT seeder FROM announce WHERE info_hash_hex='$info_hash_hex';") ) {
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
        if (defined('CACHE')) @Cache::getInstance()->set('scrape_' . $info_hash_hex, $result, 120);

        return $result;
    }
}
