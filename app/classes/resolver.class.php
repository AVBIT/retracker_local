<?php
/**
 * ----------------------------------------------------------------------------
 *                              Resolver CLASS
 * ----------------------------------------------------------------------------
 * Resolver class using Singleton pattern.
 * Only one instance of the class will be made, this requires less memory.
 * Usage: $resolver = Resolver::getInstance();
 * Usage: $info = Resolver::getInstance()->getInfoByInfoHashHex($info_hash_hex);
 * ----------------------------------------------------------------------------
 * Created by Viacheslav Avramenko aka Lordz (avbitinfo@gmail.com)
 * Created on 23.11.2016. Last modified on 01.12.2016
 * ----------------------------------------------------------------------------
 * "THE BEER-WARE LICENSE":
 * As long as you retain this notice you can do whatever you want with this stuff.
 * If we meet some day, and you think this stuff is worth it, you can buy me a beer in return.
 * ----------------------------------------------------------------------------
 */

class Resolver
{

    private static $_instance; // The single instance

    /**
     * Get an instance of the class
     * @return Instance
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
        $this->tablename_history = 'history';

        $this->db = Database::getInstance();

        //print "__construct(): " . __CLASS__ . ".class.php\n";
    }

    function __destruct()
    {
        //print "__destruct(): " . __CLASS__ . ".class.php\n";
    }

    public function resolveAllAnnounces(){
        try {
            $SQL = "SELECT * FROM $this->tablename_resolver WHERE `name`='' AND `size`<1 ORDER BY RAND() DESC LIMIT 25;";
            if ($res = $this->db->query($SQL) ){
                $unknow_announces = [];
                while ($row = $res->fetch_assoc()) $unknow_announces[]=$row;
                $res->close();

                foreach ($unknow_announces as $row){
                    $info_hash_hex = isset($row['name']) ? $row['info_hash_hex'] : '';
                    $update_time = isset($row['update_time']) ? $row['update_time'] : 0;
                    $result = $this->getInfoByInfoHashHex($info_hash_hex); // IMPORTANT!!! The operation can take a long time!
                    if (!empty($result)){
                        $name = isset($result['name']) && !empty($result['name']) ? $result['name'] : '';
                        $size = isset($result['size']) && !empty($result['size']) ? preg_replace("/[^0-9]/", '', $result['size']) : 0;
                        $comment = isset($result['comment']) && !empty($result['comment']) ? $result['comment'] : '';

                        if (!empty($name) && !empty($size)) {
                            $name = $this->db->real_escape_string($result['name']);
                            $comment = $this->db->real_escape_string($result['comment']);
                            $SQL2 = "UPDATE $this->tablename_resolver SET `name`='$name', `size`='$size', `comment`='$comment' WHERE info_hash_hex='$info_hash_hex';";
                            if ($this->db->query($SQL2) === false){
                                throw new Exception(__METHOD__ . PHP_EOL . $SQL2 . PHP_EOL. $this->db->error );
                            }
                            History::getInstance()->Save($info_hash_hex,$name,$size,$comment,$update_time);
                        }
                    }
                }

            } else {
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

    public function getInfoByInfoHashHex($info_hash_hex = null){
        $result = [];
        if (empty($info_hash_hex)) return $result;
        $info_hash_hex_sql = $this->db->real_escape_string($info_hash_hex);

        $result = $this->getStorageInfo($info_hash_hex_sql);
        if (!empty($result)) return $result;

        $result = $this->findInfo($info_hash_hex);
        if (!empty($result)) return $result;

        return $result;
    }

    private function getStorageInfo($info_hash_hex = null){

        $result = [];

        try {
            $SQL = "SELECT * FROM $this->tablename_history WHERE info_hash_hex='$info_hash_hex' AND `name`!='' AND `size`>0;";
            if ($res = $this->db->query($SQL) ){
                while ($row = $res->fetch_assoc()){
                    $result[] = $row;
                }
                $res->close();
            } else {
                throw new Exception(__METHOD__ . PHP_EOL . $SQL . PHP_EOL. $this->db->error );
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

    private function findInfo($info_hash_hex = null, $tracker = null){
        $result = [];
        if (empty($info_hash_hex)) return $result;
        $tracker = empty($tracker) ? 'http://retracker.local/announce' : urlencode($tracker);

        $command = "python magnetinfo.py -m 'magnet:?xt=urn:btih:$info_hash_hex&tr=$tracker'";

        try {
            // Code that may throw an Exception or Error.
            exec($command, $result_lines, $result_code);
            //print_r($result_lines);
            //print_r($result_code);

            if ($result_code === 2) {
                Log::getInstance()->addInfo(__METHOD__ . " $info_hash_hex - Oops ... it happens. Can not get the information. Aborting (timeout).");
                return $result;
            } elseif ($result_code !== 0) {
                $msg = 'REQUIRE INSTALLED  /usr/ports/net-p2p/libtorrent-rasterbar-python ???';
                //throw new Exception(__METHOD__ . " exec($command) " . " result_lines: ". json_encode($result_lines) ." result_code: $result_code" . $msg );
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
                $msg = __METHOD__ . " $info_hash_hex -";
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

    public function detect_encoding($string) {
        static $list = array('ASCII', 'utf-8', 'cp1251');
        foreach ($list as $item) {
            if (strcmp(@iconv($item, $item, $string), $string) == 0) return $item;
        }
        return null;
    }

}