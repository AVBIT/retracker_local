<?php
/**
 * ANNOUNCER
 * ----------------------------------------------------------------------------
 * Created by Viacheslav Avramenko aka Lordz (avbitinfo@gmail.com)
 * Created on 26.10.2016. Last modified on 21.06.2017
 * ----------------------------------------------------------------------------
 * "THE BEER-WARE LICENSE":
 * As long as you retain this notice you can do whatever you want with this stuff.
 * If we meet some day, and you think this stuff is worth it, you can buy me a beer in return.
 * ----------------------------------------------------------------------------
 */

header('Content-Type: text/plain; charset=UTF-8', true);

require_once __DIR__ . '/../app/autoload.php';

$time_start = microtime(true); // for test only!!!


// --------------------------------------------------------------------
// Initialization
// --------------------------------------------------------------------

// DB
$db = Database::getInstance();


// Garbage collector
if (!empty($_GET[$tr_cfg['run_gc_key']])) {
	$announce_interval = max(intval($tr_cfg['announce_interval']), 60);
	$expire_factor     = max(floatval($tr_cfg['peer_expire_factor']), 2);
    $diff_time  = floor($announce_interval * $expire_factor);

    $db->query("DELETE LOW_PRIORITY FROM announce WHERE update_time < (UNIX_TIMESTAMP()-$diff_time);");
    $db->query("DELETE LOW_PRIORITY FROM announce_resolver WHERE update_time < (UNIX_TIMESTAMP()-$diff_time);");
	die();
}

// Recover info_hash
if (isset($_GET['?info_hash']) && !isset($_GET['info_hash'])) {
	$_GET['info_hash'] = $_GET['?info_hash'];
}

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
    'uploaded',
    'downloaded',
    'left',
    'compact',
    'size'
);

// Init received data
// String
foreach ($input_vars_str as $var_name){
	$$var_name = isset($_GET[$var_name]) ? (string) $_GET[$var_name] : null;
}
// Numeric
foreach ($input_vars_num as $var_name){
	$$var_name = isset($_GET[$var_name]) ? (float) $_GET[$var_name] : null;
}

// Verify required request params
if (!isset($info_hash) || strlen($info_hash) != 20) msg_die('Invalid info_hash');
if (!isset($peer_id) || strlen($peer_id) != 20) msg_die('Invalid peer_id');
if (!isset($port) || $port < 0 || $port > 0xFFFF) msg_die('Invalid port');
if (!isset($uploaded) || $uploaded < 0) msg_die('Invalid uploaded value');
if (!isset($downloaded) || $downloaded < 0) msg_die('Invalid downloaded value');
if (!isset($left) || $left < 0) msg_die('Invalid left value');

// IP
$ip = $_SERVER['REMOTE_ADDR'];
if (!$tr_cfg['ignore_reported_ip'] && isset($_GET['ip']) && $ip !== $_GET['ip']) {
	if (!$tr_cfg['verify_reported_ip'])	{
		$ip = $_GET['ip'];
	} else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && preg_match_all('#\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#', $_SERVER['HTTP_X_FORWARDED_FOR'], $matches)) {
		foreach ($matches[0] as $x_ip) {
			if ($x_ip === $_GET['ip']) {
				if (!$tr_cfg['allow_internal_ip'] && preg_match("#^(10|172\.16|192\.168)\.#", $x_ip)) break;
				$ip = $x_ip;
				break;
			}
		}
	}
}

// Check that IP format is valid
if (!$iptype = verify_ip($ip)) msg_die("Invalid IP: $ip");

// Convert IP to HEX format
$ip_sql = encode_ip($ip);


// --------------------------------------------------------------------
// Start announcer
// --------------------------------------------------------------------
$info_hash_sql = rtrim($db->real_escape_string($info_hash), ' ');
$info_hash_hex = bin2hex($info_hash);
$info_hash_hex_sql = $db->real_escape_string($info_hash_hex);

// Peer unique id
$peer_hash = md5(rtrim($info_hash, ' ') . $peer_id . $ip . $port);

// It's seeder?
$seeder = ($left == 0) ? 1 : 0;

// Stopped event
if ($event === 'stopped'){
    $db->query("DELETE FROM announce WHERE peer_hash='$peer_hash' AND info_hash_hex = '$info_hash_hex_sql';");
	die();
}

$ipv6 = ($iptype == 'ipv6') ? encode_ip($ip) : ((verify_ip($ipv6) == 'ipv6') ? encode_ip($ipv6) : null);
$ipv4 = ($iptype == 'ipv4') ? encode_ip($ip) : ((verify_ip($ipv4) == 'ipv4') ? encode_ip($ipv4) : null);

$columns = $values = array();

$columns[] = "`peer_hash`";
$columns[] = "`ip`";
$columns[] = "`ipv6`";
$columns[] = "`port`";
$columns[] = "`seeder`";
$columns[] = "`info_hash_hex`";
$columns[] = "`name`";
$columns[] = "`size`";
$columns[] = "`comment`";
$columns[] = "`uploaded`";
$columns[] = "`downloaded`";
$columns[] = "`left`";
$columns[] = "`update_time`";

$values[] = "'" . $db->real_escape_string($peer_hash) . "'";
$values[] = "'" . $db->real_escape_string($ipv4) . "'";
$values[] = "'" . $db->real_escape_string($ipv6) . "'";
$values[] = (int)$port;
$values[] = (int)$seeder;
$values[] = "'" . $db->real_escape_string($info_hash_hex) . "'";
$values[] = "'" . $db->real_escape_string($name) . "'";
$values[] = (int)$size;
$values[] = "'" . $db->real_escape_string($comment) . "'";
$values[] = (int)$uploaded;
$values[] = (int)$downloaded;
$values[] = (int)$left;
$values[] = "UNIX_TIMESTAMP()";

// Update peer info
$SQL = "REPLACE DELAYED INTO announce (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ")";
$db->query($SQL);


// PREPARATION OUTPUT
// Retrieve peers
$output = false; // cache???

if (!$output) {
    $limit = (int)(($numwant > $tr_cfg['numwant']) ? $tr_cfg['numwant'] : $numwant);
    $compact_mode = ($tr_cfg['compact_always'] || !empty($compact));

    $SQL = "SELECT `ip`, `ipv6`, `port` 
		    FROM announce 
		    WHERE `info_hash_hex` = '$info_hash_hex_sql' 
		    ORDER BY RAND() 
		    LIMIT $limit";

    if ($res = $db->query($SQL) ){
        // Pack peers if compact mode
        if ($compact_mode) {
            $peerset = $peerset6 = '';
            while ($row = $res->fetch_assoc()){
                if (!empty($row['ip'])) {
                    $peerset .= pack('Nn', ip2long(decode_ip($row['ip'])), $row['port']);
                }
                if (!empty($row['ipv6'])) {
                    $peerset6 .= pack('H32n', $row['ipv6'], $row['port']);
                }
            }
        } else {
            $peerset = $peerset6 = array();
            while ($row = $res->fetch_assoc()){
                if (!empty($row['ip'])) {
                    $peerset[] = array(
                        'ip' => decode_ip($row['ip']),
                        'port' => intval($row['port'])
                    );
                }
                if (!empty($row['ipv6'])) {
                    $peerset6[] = array(
                        'ip' => decode_ip($row['ipv6']),
                        'port' => intval($row['port'])
                    );
                }
            }
        }
        $res->close();
    }


    $seeders = $peers = $leechers = 0;
    $SQL = "SELECT SUM(`seeder`) AS `seeders`, COUNT(*) AS `peers`
			FROM announce 
			WHERE `info_hash_hex` = '$info_hash_hex_sql' 
			";
    if ($res = $db->query($SQL) ){
        $row = $res->fetch_assoc();
        $seeders = (int)$row['seeders'];
        $peers = (int)$row['peers'];
        $leechers = $peers - $seeders;
        $res->close();
    }

    Announce::getInstance()->Save($info_hash_hex,$name,(int)$size,$comment,(int)$seeders,(int)$leechers);

    // Generate output
    $ann_interval = $tr_cfg['announce_interval'] + mt_rand(0, 60);
    $output = array(
        'interval' => (int)$ann_interval,
        //'min interval' => (int)$ann_interval,  // tracker config: min interval (sec?)
        'peers' => isset($peerset)? $peerset : [],
        'peers6' => isset($peerset6)? $peerset6 : [],
        'complete' => (int)$seeders,
        'incomplete' => (int)$leechers
    );

}


// Return data to client
echo bencode($output);


// TEST
if (mt_rand(1, 100) <= 1) {
    $str = sprintf("ANNOUNCE: EXECUTIONTIME: %.5F sec. MEMORY: %d bytes", microtime(true) - $time_start, memory_get_usage() );
    Log::getInstance()->addDebug($str);
}


exit;