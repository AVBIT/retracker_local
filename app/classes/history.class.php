<?php
/**
 * History CLASS
 * ----------------------------------------------------------------------------
 * History class using Singleton pattern.
 * Only one instance of the class will be made, this requires less memory.
 * Usage: $history = History::getInstance();
 * ----------------------------------------------------------------------------
 * Created by Viacheslav Avramenko aka Lordz (avbitinfo@gmail.com)
 * Created on 10.11.2016. Last modified on 06.09.2017
 * ----------------------------------------------------------------------------
 * "THE BEER-WARE LICENSE":
 * As long as you retain this notice you can do whatever you want with this stuff.
 * If we meet some day, and you think this stuff is worth it, you can buy me a beer in return.
 * ----------------------------------------------------------------------------
 */


class History {

    private static $_instance; // The single instance
    private static $tablename = 'history';

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
        $this->db = Database::getInstance();
        //print "__construct(): " . __CLASS__ . ".class.php\n";
    }

    function __destruct() {
        //print "__destruct(): " . __CLASS__ . ".class.php\n";
    }

    public static function getTableName(){
        return self::$tablename;
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
        $SQL = "SELECT
                  history.info_hash_hex,
                  history.`name`,
                  history.size,
                  history.`comment`,
                  history.update_time,
                  history.reg_time,
                  COALESCE(announce_resolver.seeders, 0) AS seeders, 
                  COALESCE(announce_resolver.leechers, 0) AS leechers
                FROM ".History::getTableName()." 
                LEFT JOIN announce_resolver ON history.info_hash_hex = announce_resolver.info_hash_hex
                WHERE MATCH (history.`name`,history.`comment`, history.info_hash_hex) AGAINST ('$search_query_sql');
              ";
        //echo $SQL . PHP_EOL . '<br>';

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
                  history.info_hash_hex,
                  history.`name`,
                  history.size,
                  history.`comment`,
                  history.update_time,
                  history.reg_time,
                  COALESCE(announce_resolver.seeders, 0) AS seeders, 
                  COALESCE(announce_resolver.leechers, 0) AS leechers
                FROM ".History::getTableName()." 
                LEFT JOIN announce_resolver ON history.info_hash_hex = announce_resolver.info_hash_hex
                WHERE history.`name` LIKE '%$search_query_sql%' OR history.`name` LIKE '$search_query_sql%' OR history.`name` LIKE '%$search_query_sql'
                ORDER BY update_time DESC
                LIMIT 1000;
                ";
            //echo $SQL . PHP_EOL . '<br>';

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

    public function Save($info_hash_hex,$name,$size=0,$comment='',$update_time=0){

        try {
            if (empty($info_hash_hex) || empty($name)) return;
            $info_hash_hex = $this->db->real_escape_string($info_hash_hex);
            $name = $this->db->real_escape_string($name);
            $comment = $this->db->real_escape_string($comment);
            $size = is_numeric($size) ? (int)$size : 0;
            $update_time = !is_numeric($update_time) || empty($update_time) ? 'UNIX_TIMESTAMP()' : (int)$update_time;

            /*
            $SQL = "INSERT DELAYED INTO $this->tablename
				  (info_hash_hex, `name`, `size`, `comment`, reg_time, update_time)
			    VALUES
				  ('$info_hash_hex', '$name', " . ($size > 0 ? $size : 0) . ", '$comment', UNIX_TIMESTAMP(), UNIX_TIMESTAMP())
			    ON DUPLICATE KEY UPDATE 
			        `name`= IF (LENGTH(`name`)<LENGTH('$name'),'$name',`name`),
			        `size`= IF (`size`<$size,'$size',`size`),
			        `comment`= IF (LENGTH(`comment`)<LENGTH('$comment'),'$comment',`comment`),
			        `update_time`= IF (`update_time`<$update_time, $update_time, `update_time`);
			";
            */

            $SQL = "INSERT DELAYED INTO ".History::getTableName()."
				  (info_hash_hex, `name`, `size`, `comment`, reg_time, update_time)
			    VALUES
				  ('$info_hash_hex', '$name', " . ($size > 0 ? $size : 0) . ", '$comment', UNIX_TIMESTAMP(), UNIX_TIMESTAMP())
			    ON DUPLICATE KEY UPDATE 
			        `name`= '$name',
			        `size`= '$size',
			        `comment`= '$comment',
			        `update_time`= $update_time;
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

    }

    private function findAll($limit=100000, $offset=0, $orderBy=[]){
        $result = [];
        $limit = (int)$limit;
        $offset = (int)$offset;
        $allowed_key = array('name','size','comment','reg_time','update_time');
        foreach ($orderBy as $key => $value){
            if (!in_array($key, $allowed_key)) unset($orderBy[$key]);
        }
        if (!$orderBy) $orderBy = array('update_time' => 'DESC'); // Set default orderBy

        $arr = [];
        foreach ($orderBy as $key => $value) $arr[] = "$key $value";
        $orderBy = implode(',', $arr);
        $SQL = "SELECT
                  history.info_hash_hex,
                  history.`name`,
                  history.size,
                  history.`comment`,
                  history.update_time,
                  history.reg_time,
                  COALESCE(announce_resolver.seeders, 0) AS seeders, 
                  COALESCE(announce_resolver.leechers, 0) AS leechers
                FROM ".History::getTableName()."  
                LEFT JOIN announce_resolver ON history.info_hash_hex = announce_resolver.info_hash_hex
                ORDER BY $orderBy LIMIT $limit OFFSET $offset;
        ";

        if ($res = $this->db->query($SQL) ) {
            while ($row = $res->fetch_assoc()) {
                if (isset($row['name'])) $row['name'] = mb_convert_encoding($row['name'], "UTF-8", "CP1251");

                // create URL and URN
                if (isset($row['info_hash_hex']) && isset($row['name']) && isset($row['size']) && isset($row['comment'])) {
                    $row['magnet_urn'] = Uri::makeMagnetURN($row['info_hash_hex'],$row['name'],$row['size'],$row['comment']);
                }
                if (isset($row['comment'])) {
                    $row['comment'] = Uri::makeURL($row['comment']);
                }
                $result[] = $row;
            }
            $res->close();
        }

        return $result;
    }

    public function getPageOfList($page = 1, $row_in_page = 50, $orderBy=[]){

        $result['page_num'] = (int)$page;
        $result['pages'] = floor($this->db->getTableRecordsCount(History::getTableName()) / (int)$row_in_page) +1;

        if ((int)$page < 1) $page = 1;
        $offset = (int)$page*$row_in_page-$row_in_page;

        // Read from cache
        $cache_key = 'history_p'.$page.implode('',$orderBy);
        if (defined('CACHE')){
            $cache_result=@Cache::getInstance()->get( $cache_key );
            if (!empty($cache_result)) return $cache_result;
        }

        $result['result'] = $this->findAll($row_in_page,$offset,$orderBy);

        // Save to cache
        if (defined('CACHE')) @Cache::getInstance()->set( $cache_key , $result, 120);

        return $result;
    }

}
