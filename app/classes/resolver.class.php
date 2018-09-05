<?php
/**
 * Resolver CLASS
 * ----------------------------------------------------------------------------
 * Resolver class using Singleton pattern.
 * Only one instance of the class will be made, this requires less memory.
 * Usage: $resolver = Resolver::getInstance();
 * Usage: $info = Resolver::getInstance()->getInfoByInfoHashHex($info_hash_hex);
 * ----------------------------------------------------------------------------
 * Created by Viacheslav Avramenko aka Lordz (avbitinfo@gmail.com)
 * Created on 23.11.2016. Last modified on 05.09.2018
 * ----------------------------------------------------------------------------
 * "THE BEER-WARE LICENSE":
 * As long as you retain this notice you can do whatever you want with this stuff.
 * If we meet some day, and you think this stuff is worth it, you can buy me a beer in return.
 * ----------------------------------------------------------------------------
 */

class Resolver
{
    //public $threads = 1;    // multithreading (number of resolver threads)

    private static $_instance; // The single instance

    /**
     * Get an instance of the class
     * @return Resolver
     */
    public static function getInstance()
    {
        if (!self::$_instance) {
            // If no instance then make one
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function __construct()
    {
        $this->tablename_resolver = 'announce_resolver';
        $this->tablename_unresolved = 'announce_unresolved';
        $this->tablename_history = 'history';

        $this->db = Database::getInstance();

        //print "__construct(): " . __CLASS__ . ".class.php\n";
    }

    function __destruct()
    {
        //print "__destruct(): " . __CLASS__ . ".class.php\n";
    }

    public function run(){
        // multithreading
    }

    public function resolveAllKnown(){
        try {
            $SQL = "SELECT
                      $this->tablename_resolver.info_hash_hex,
                      $this->tablename_history.`name` AS history_name,
                      $this->tablename_history.`size` AS history_size,
                      $this->tablename_history.`comment` AS history_comment
                  FROM $this->tablename_resolver
                  LEFT OUTER JOIN $this->tablename_history ON $this->tablename_resolver.info_hash_hex = $this->tablename_history.info_hash_hex
                  WHERE ($this->tablename_resolver.`name`='' AND $this->tablename_resolver.`size`<1) AND (NOT ISNULL($this->tablename_history.`name`) AND $this->tablename_history.`size`>0);
                  ";
            if ($res = $this->db->query($SQL) ){
                $i=0;
                while ($row = $res->fetch_assoc()) {
                    $info_hash_hex = $row['info_hash_hex'];
                    $history_name = $this->db->real_escape_string($row['history_name']);
                    $history_size = (int)$row['history_size'];
                    $history_comment = $this->db->real_escape_string($row['history_comment']);

                    $subSQL = "UPDATE $this->tablename_resolver SET `name`='$history_name',`size`='$history_size',`comment`='$history_comment' WHERE info_hash_hex='$info_hash_hex';";
                    if ($this->db->query($subSQL) === false){
                        throw new Exception(__METHOD__ . PHP_EOL . $subSQL . PHP_EOL. $this->db->error . PHP_EOL );
                    }
                    //Log::getInstance()->addTrace(__METHOD__ . " affected_rows:" . $this->db->affected_rows);
                    $i++;
                }
                $res->close();
                Log::getInstance()->addInfo(__METHOD__ . " FOUND IN HISTORY AND RESOLVED - $i");
            } else {
                throw new Exception(__METHOD__ . PHP_EOL . $SQL . PHP_EOL. $this->db->error . PHP_EOL );
            }

        } catch (Throwable $t) {
            // Executed only in PHP 7, will not match in PHP 5.x
            Log::getInstance()->addError($t->getMessage());
        } catch (Exception $e) {
            // Executed only in PHP 5.x, will not be reached in PHP 7
            Log::getInstance()->addError($e->getMessage());
        }
    }

    public function resolveAllAnnounces(){

        $this->resolveAllKnown();
        // resolve other
        try {
            if (mt_rand(1, 1000) <= 1) {
                $SQL = "DELETE FROM $this->tablename_unresolved WHERE update_time < 86400*7;";
                if (!$this->db->query($SQL) ){
                    throw new Exception(__METHOD__ . PHP_EOL . $SQL . PHP_EOL. $this->db->error );
                }
            }

            $SQL = "SELECT
                      $this->tablename_resolver.info_hash_hex,
                      $this->tablename_resolver.update_time,
                      $this->tablename_unresolved.attempts
                    FROM $this->tablename_resolver
                    LEFT OUTER JOIN $this->tablename_unresolved ON $this->tablename_resolver.info_hash_hex = $this->tablename_unresolved.info_hash_hex
                    WHERE `name`='' AND `size`<1 AND (ISNULL(attempts) OR attempts < 3) 
                    ORDER BY attempts ASC
                    LIMIT 30;
                  ";
            if ($res = $this->db->query($SQL) ){
                $unknow_announces = [];
                while ($row = $res->fetch_assoc()) $unknow_announces[]=$row;
                $res->close();

                foreach ($unknow_announces as $row){
                    $info_hash_hex = isset($row['info_hash_hex']) && !empty($row['info_hash_hex']) ? $row['info_hash_hex'] : '';
                    $update_time = isset($row['update_time']) && !empty($row['update_time']) ? $row['update_time'] : 0;
                    $attempts = isset($row['attempts']) && !empty($row['attempts']) ? $row['attempts']+1 : '';

                    Log::getInstance()->addInfo(__METHOD__ . " $info_hash_hex - ATTEMPT SEARCH " . $attempts);

                    $result = $this->getInfoByInfoHashHex($info_hash_hex); // IMPORTANT!!! The operation can take a long time!
                    if (!empty($result)){
                        $name = isset($result['name']) && !empty($result['name']) ? $result['name'] : '';
                        $size = isset($result['size']) && !empty($result['size']) ? preg_replace("/[^0-9]/", '', $result['size']) : 0;
                        $comment = isset($result['comment']) && !empty($result['comment']) ? $result['comment'] : '';

                        if (!empty($name) && !empty($size)) {
                            $name = $this->db->real_escape_string($name);
                            $comment = $this->db->real_escape_string($comment);
                            $SQL2 = "UPDATE $this->tablename_resolver SET `name`='$name', `size`='$size', `comment`='$comment' WHERE info_hash_hex='$info_hash_hex';";
                            if ($this->db->query($SQL2) === false){
                                throw new Exception(__METHOD__ . PHP_EOL . $SQL2 . PHP_EOL. $this->db->error );
                            }
                            History::getInstance()->Save($info_hash_hex,$name,$size,$comment,$update_time);
                        }
                    }
                }

            } else {
                throw new Exception(__METHOD__ . PHP_EOL . $SQL . PHP_EOL . $this->db->error . PHP_EOL );
            }

        } catch (Throwable $t) {
            // Executed only in PHP 7, will not match in PHP 5.x
            Log::getInstance()->addError($t->getMessage());
        } catch (Exception $e) {
            // Executed only in PHP 5.x, will not be reached in PHP 7
            Log::getInstance()->addError($e->getMessage());
        }
    }

    public function getInfoByInfoHashHex($info_hash_hex = null){
        $result = [];
        if (empty($info_hash_hex)) return $result;
        $info_hash_hex_sql = $this->db->real_escape_string($info_hash_hex);

        $result = $this->findInfoInternal($info_hash_hex_sql);
        if (!empty($result)) return $result;

        $result = $this->findInfoExternal($info_hash_hex);
        if (!empty($result)) return $result;

        return $result;
    }

    private function findInfoInternal($info_hash_hex = null){

        $result = [];

        try {
            $SQL = "SELECT * FROM $this->tablename_history WHERE info_hash_hex='$info_hash_hex';"; // info_hash_hex - primary key!
            if ($res = $this->db->query($SQL) ){
                $result = $res->fetch_assoc();
                $res->close();
            } else {
                throw new Exception(__METHOD__ . PHP_EOL . $SQL . PHP_EOL. $this->db->error . PHP_EOL );
            }

            if (defined('LOG_LEVEL')){
                if (!empty($result)){
                    $msg = __METHOD__ . " $info_hash_hex - FOUND IN HISTORY";
                    if (!empty($result['name'])) $msg .= ' name:'.$result['name'];
                    if (!empty($result['size'])) $msg .= ' size:'.$result['size'];
                    if (!empty($result['comment'])) $msg .= ' comment:'.$result['comment'];
                    $msg = mb_convert_encoding($msg, 'UTF-8', 'CP1251');
                    Log::getInstance()->addInfo($msg);
                } else {
                    Log::getInstance()->addInfo(__METHOD__ . " $info_hash_hex - NOT FOUND IN HISTORY");
                }
            }

        } catch (Throwable $t) {
            // Executed only in PHP 7, will not match in PHP 5.x
            Log::getInstance()->addError($t->getMessage());
        } catch (Exception $e) {
            // Executed only in PHP 5.x, will not be reached in PHP 7
            Log::getInstance()->addError($e->getMessage());
        }

        return $result;
    }

    private function findInfoExternal($info_hash_hex = null, $tracker = null){
        $result = [];
        if (empty($info_hash_hex)) return $result;

        $trackers[] = empty($tracker) ? 'http://retracker.local/announce' : $tracker;
        if (defined('OPEN_TRACKERS')){
            $arr = preg_split("/[\s,;|]+/", OPEN_TRACKERS);
            $trackers = array_merge($trackers, $arr);
        }

        $tracker_str='';
        foreach ($trackers as $tracker) $tracker_str .= "&tr=$tracker";

        $command = "python magnetinfo.py -m 'magnet:?xt=urn:btih:" . $info_hash_hex . $tracker_str . "'";
        //Log::getInstance()->addTrace($command);

        try {
            // Code that may throw an Exception or Error.
            exec($command, $result_lines, $result_code);
            //print_r($result_lines);
            //print_r($result_code);

            if ($result_code === 2) {
                Log::getInstance()->addInfo(__METHOD__ . " $info_hash_hex - UNRESOLVED Oops ... it happens. Can not get the information. Aborting (timeout).");
                $this->markAsUnresolved($info_hash_hex);
                return $result;
            } elseif ($result_code !== 0) {
                $msg = 'REQUIRE INSTALLED  /usr/ports/net-p2p/libtorrent-rasterbar-python ???';
                Log::getInstance()->addWarning(__METHOD__ . " exec($command) " . " result_lines: ". json_encode($result_lines) ." result_code: $result_code" . $msg);
                return $result;
            }

            // Success result_code(0)
            $result['info_hash_hex']=$info_hash_hex;
            $result['name']='';
            $result['size']=0;
            $result['comment']='';

            foreach ($result_lines AS $line){
                if ( strpos($line,"File_size:")===0 ) $result['size'] = (int)substr($line,11);
                if ( strpos($line,"File_name:")===0 ) $result['name'] = substr($line,11);
                if ( strpos($line,"File_comment:")===0 ) $result['comment'] = substr($line,14);
            }

            if (defined('LOG_LEVEL') && LOG_LEVEL<=Log::INFO){
                $msg = __METHOD__ . " $info_hash_hex - RESOLVED";
                if (!empty($result['name'])) $msg .= ' name:'.$result['name'];
                if (!empty($result['size'])) $msg .= ' size:'.$result['size'];
                if (!empty($result['comment'])) $msg .= ' comment:'.$result['comment'];
                Log::getInstance()->addInfo($msg);
            }

            // Test encoding (Cyrillic alphabet)
            //Log::getInstance()->addTrace("detect_encoding: " . $this->detect_encoding($result['name']));
            $result['name'] = ($this->detect_encoding($result['name'])=='utf-8') ? mb_convert_encoding($result['name'], 'CP1251', "UTF-8") : $result['name']; // IMPORTANT!!! may be UTF-8 and/or cyrillic alphabet

            if (empty($result['name']) || empty($result['size'])) $result = [];

        } catch (Throwable $t) {
            // Executed only in PHP 7, will not match in PHP 5.x
            Log::getInstance()->addError($t->getMessage());
        } catch (Exception $e) {
            // Executed only in PHP 5.x, will not be reached in PHP 7
            Log::getInstance()->addError($e->getMessage());
        }

        return $result;
    }

    private function markAsUnresolved ($info_hash_hex = null){
        try {
            $SQL = "INSERT DELAYED INTO $this->tablename_unresolved
				  (info_hash_hex, attempts, update_time)
			    VALUES
				  ('$info_hash_hex', 1, UNIX_TIMESTAMP())
			    ON DUPLICATE KEY UPDATE 
			        attempts = attempts + 1,
			        update_time = UNIX_TIMESTAMP();
			";

            if ($this->db->query($SQL) === false){
                throw new Exception(__METHOD__ . PHP_EOL . $SQL . PHP_EOL. $this->db->error . PHP_EOL );
            }

            Log::getInstance()->addInfo(__METHOD__ . ' ' . $info_hash_hex);

        } catch (Throwable $t) {
            // Executed only in PHP 7, will not match in PHP 5.x
            Log::getInstance()->addError($t->getMessage());
        } catch (Exception $e) {
            // Executed only in PHP 5.x, will not be reached in PHP 7
            Log::getInstance()->addError($e->getMessage());
        }
    }

    public function detect_encoding($string) {
        static $list = array('ASCII', 'utf-8', 'cp1251');
        foreach ($list as $item) {
            if (strcmp(@iconv($item, $item, $string), $string) == 0) return $item;
        }
        return null;
    }

}