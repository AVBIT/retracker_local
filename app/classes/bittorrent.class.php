<?php
/**
 * ----------------------------------------------------------------------------
 *                              BitTorrent CLASS
 * ----------------------------------------------------------------------------
 * BitTorrent class using Singleton pattern.
 * Only one instance of the class will be made, this requires less memory.
 * Usage: $bt = BitTorrent::getInstance();
 * ----------------------------------------------------------------------------
 * Created by Viacheslav Avramenko aka Lordz (avbitinfo@gmail.com)
 * Created on 10.11.2016. Last modified on 16.11.2016
 * ----------------------------------------------------------------------------
 * "THE BEER-WARE LICENSE":
 * As long as you retain this notice you can do whatever you want with this stuff.
 * If we meet some day, and you think this stuff is worth it, you can buy me a beer in return.
 * ----------------------------------------------------------------------------
 */


class BitTorrent {

    private static $_instance; // The single instance

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

        $this->tablename = 'bittorrent';
        $this->db = Database::getInstance();

        //print "__construct(): " . __CLASS__ . ".class.php\n";
    }

    function __destruct() {
        //print "__destruct(): " . __CLASS__ . ".class.php\n";
    }

    public function Search($search_query, $page = 1, $row_in_page = 20) {

        $result['page_num'] = 1;
        $result['pages'] = 1;
        $result['search_query'] = strip_tags($search_query);
        $result['result'] = [];

        if (empty($search_query)) return $result;
        $search_query = mb_convert_encoding(strip_tags($search_query), "CP1251", "UTF-8");

        if ((int)$row_in_page < 10) $row_in_page = 10;
        if ((int)$page < 1) $page = 1;
        $offset = (int)$page*$row_in_page-$row_in_page;

        $search_query_hash = SHA1($search_query);

        // Read from cache
        $cache_key = 'search_p'.$page.'_'. $search_query_hash;
        if (defined('CACHE')){
            $cache_result=@Cache::getInstance()->get( $cache_key );
            if (!empty($cache_result)) return $cache_result;
        }

        $search_query_sql = $this->db->real_escape_string($search_query);
        $result_arr = [];

        // Fulltext search
        $SQL = "SELECT * FROM $this->tablename WHERE MATCH (`name`,`comment`, info_hash_hex) AGAINST ('$search_query_sql');"; // AGAINST ('$search_query_sql'  IN BOOLEAN MODE)

        if ($res = $this->db->query($SQL) ){
            while ($row = $res->fetch_assoc()){
                if (isset($row['name'])) $row['name'] = mb_convert_encoding($row['name'], "UTF-8", "CP1251");

                // create URL and URN
                if (isset($row['info_hash_hex']) && isset($row['name']) && isset($row['size']) && isset($row['comment'])) {
                    $row['magnet_urn'] = Uri::makeMagnetURN($row['info_hash_hex'],$row['name'],$row['size'],$row['comment']);
                }
                if (isset($row['comment'])) {
                    $row['comment'] = Uri::makeURL($row['comment']);
                }

                $result_arr[] = $row;
            }
            $res->close();
        }

        // if empty do Like search
        if (empty($result_arr)){

            $SQL = "SELECT
                  info_hash_hex,
                  seeders,
                  leechers,
                  `name`,
                  `size`,
                  `comment`,
                  update_time
                FROM
                  $this->tablename
                WHERE `name` LIKE '%$search_query_sql%' OR `name` LIKE '$search_query_sql%' OR `name` LIKE '%$search_query_sql'
                ORDER BY update_time DESC
                LIMIT 1000;
                ";

            if ($res = $this->db->query($SQL) ){
                while ($row = $res->fetch_assoc()){
                    if (isset($row['name'])) $row['name'] = mb_convert_encoding($row['name'], "UTF-8", "CP1251");

                    // create URL and URN
                    if (isset($row['info_hash_hex']) && isset($row['name']) && isset($row['size']) && isset($row['comment'])) {
                        $row['magnet_urn'] = Uri::makeMagnetURN($row['info_hash_hex'],$row['name'],$row['size'],$row['comment']);
                    }
                    if (isset($row['comment'])) {
                        $row['comment'] = Uri::makeURL($row['comment']);
                    }

                    $result_arr[] = $row;
                }
                $res->close();
            }
        }

        $result['page_num'] = (int)$page;
        $result['pages'] = floor(count($result_arr) / (int)$row_in_page)+1;
        if ($result['page_num']>$result['pages']) $this->Search($search_query, 1,$row_in_page);
        $result['result'] = array_slice($result_arr, $offset, $offset+$row_in_page);

        // Save to cache
        if (defined('CACHE')) @Cache::getInstance()->set( $cache_key , $result, 120);

        return $result;
    }

    public function Save($info_hash_hex,$name,$size,$comment,$seeders=0,$leechers=0){

        if (empty($info_hash_hex) || empty($name) || empty($size) || empty($comment)) return;

        $SQL = "REPLACE DELAYED INTO $this->tablename
				(info_hash_hex, seeders, leechers, `name`, `size`, `comment`, update_time)
			VALUES
				('$info_hash_hex', '$seeders', '$leechers', '$name', " . ($size > 0 ? $size : 0) . ", '$comment', UNIX_TIMESTAMP());
			";
        $this->db->query($SQL);
        //file_put_contents('/tmp/retracker_profiler', $SQL, FILE_APPEND);
    }

    public function getHumanReadable($page = 1, $row_in_page = 20){

        $result['page_num'] = (int)$page;
        $result['pages'] = ceil($this->getTableRecordsCount() / (int)$row_in_page)-1;
        $result['result'] = [];

        if ((int)$row_in_page < 10) $row_in_page = 10;
        if ((int)$page < 1) $page = 1;
        $offset = (int)$page*$row_in_page-$row_in_page;


        // Read from cache
        $cache_key = 'history_p'.$page;
        if (defined('CACHE')){
            $cache_result=@Cache::getInstance()->get( $cache_key );
            if (!empty($cache_result)) return $cache_result;
        }

        $SQL = "SELECT * FROM $this->tablename ORDER BY update_time DESC LIMIT $row_in_page OFFSET $offset;";
        //echo $SQL;
        if ($res = $this->db->query($SQL) ){
            while ($row = $res->fetch_assoc()){
                if (isset($row['name'])) $row['name'] = mb_convert_encoding($row['name'], "UTF-8", "CP1251");

                // create URL and URN
                if (isset($row['info_hash_hex']) && isset($row['name']) && isset($row['size']) && isset($row['comment'])) {
                    $row['magnet_urn'] = Uri::makeMagnetURN($row['info_hash_hex'],$row['name'],$row['size'],$row['comment']);
                }
                if (isset($row['comment'])) {
                    $row['comment'] = Uri::makeURL($row['comment']);
                }

                $result['result'][] = $row;
            }
            $res->close();
        }

        // Save to cache
        if (defined('CACHE')) @Cache::getInstance()->set( $cache_key , $result, 120);

        return $result;
    }

    public function getTableRecordsCount(){
        $result = 0;
        $SQL = "SELECT table_name, table_rows
                FROM INFORMATION_SCHEMA.TABLES
                WHERE TABLE_SCHEMA = '".DB_NAME."' AND table_name='$this->tablename';";
        //echo $SQL;
        if ($res = $this->db->query($SQL) ){
            while ($row = $res->fetch_assoc()){
                $result = $row['table_rows'];
            }
            $res->close();
        }
        return $result;
    }

}
