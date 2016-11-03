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
 * Created on 27.03.2016. Last modified on 02.11.2016
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

    public function getHumanReadable($is_with_info = false){
        $result = [];
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
ORDER by update_time";

        //echo $SQL;
        //$this->db->query("SET CHARACTER SET 'utf-8';");
        if ($res = $this->db->query($SQL) ){
            while ($row = $res->fetch_assoc()){
                if (isset($row['name'])) $row['name'] = mb_convert_encoding($row['name'], "UTF-8", "CP1251");
                $result[] = $row;
            }
            $res->close();
        }
        return $result;
    }

    private function actionCompleted ($arr){

    }
    private function actionStarted  ($arr){

    }
    private function actionStopped ($arr){

    }
}
