<?php
/**
 * ----------------------------------------------------------------------------
 *                              ANNOUNCER
 * ----------------------------------------------------------------------------
 * Created by Viacheslav Avramenko aka Lordz (avbitinfo@gmail.com)
 * Created on 26.10.2016. Last modified on 27.10.2016
 * ----------------------------------------------------------------------------
 * "THE BEER-WARE LICENSE":
 * As long as you retain this notice you can do whatever you want with this stuff.
 * If we meet some day, and you think this stuff is worth it, you can buy me a beer in return.
 * ----------------------------------------------------------------------------
 */


require_once '../app/autoload.php';

define('TIMENOW', time());


// --------------------------------------------------------------------
// Initialization
// --------------------------------------------------------------------

// DB
$db = Database::getInstance();


// Garbage collector
if (!empty($_GET[$tr_cfg['run_gc_key']])) {
	$announce_interval = max(intval($tr_cfg['announce_interval']), 60);
	$expire_factor     = max(floatval($tr_cfg['peer_expire_factor']), 2);
	$peer_expire_time  = TIMENOW - floor($announce_interval * $expire_factor);

	$db->query("DELETE FROM tracker_new WHERE update_time < $peer_expire_time;");
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
if (!isset($port) || $port < 0 || $port > 0xFFFF) msg_die('Invalid port');
if (!isset($peer_id) || strlen($peer_id) != 20) msg_die('Invalid peer_id');
if (!isset($port) || $port < 0 || $port > 0xFFFF) msg_die('Invalid port');
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
$info_hash_hex = bin2hex($info_hash);
$info_hash_sql = rtrim($db->real_escape_string($info_hash), ' ');

// Peer unique id
$peer_hash = md5(rtrim($info_hash, ' ') . $peer_id . $ip . $port);

// It's seeder?
$seeder = ($left == 0) ? 1 : 0;

// Stopped event
if ($event === 'stopped'){
	$db->query("DELETE FROM tracker WHERE info_hash = '$info_hash_sql' AND ip = '$ip_sql' AND port = $port;");
	die();
}

$torrent_id = 0;
$SQL = "SELECT torrent_id
		FROM tracker_stats_new
		WHERE info_hash_hex = '$info_hash_hex'
		LIMIT 1;";
if ($res = $db->query($SQL) ) {
    $row = $res->fetch_assoc();
    $torrent_id = (int)$row['torrent_id'];
    $res->close();
}

if (!$torrent_id) {
    $SQL = "INSERT INTO tracker_stats_new
				(info_hash_hex, reg_time, update_time, `name`, `size`, `comment`)
			VALUES
				('$info_hash_hex', " . TIMENOW . ", " . TIMENOW . ", '$name', " . ($size > 0 ? $size : 0) . ", '$comment');
			";
    if ($res = $db->query($SQL) ) {
        $torrent_id = $db->insert_id;
        $res->close();
    }
}

$ipv6 = ($iptype == 'ipv6') ? encode_ip($ip) : ((verify_ip($ipv6) == 'ipv6') ? encode_ip($ipv6) : null);
$ipv4 = ($iptype == 'ipv4') ? encode_ip($ip) : ((verify_ip($ipv4) == 'ipv4') ? encode_ip($ipv4) : null);

$columns = $values = array();

$columns[] = "`torrent_id`";
$columns[] = "`peer_hash`";
$columns[] = "`ip`";
$columns[] = "`ipv6`";
$columns[] = "`port`";
$columns[] = "`seeder`";
$columns[] = "`update_time`";

$values[] = (int)$torrent_id;
$values[] = "'" . $db->real_escape_string($peer_hash) . "'";
$values[] = "'" . $db->real_escape_string($ipv4) . "'";
$values[] = "'" . $db->real_escape_string($ipv6) . "'";
$values[] = (int)$port;
$values[] = (int)$seeder;
$values[] = TIMENOW;

// Update peer info
$db->query("REPLACE INTO tracker_new (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ")");


// PREPARATION OUTPUT
// Retrieve peers
$output = false; // cache???

if (!$output) {
    $limit = (int)(($numwant > $tr_cfg['numwant']) ? $tr_cfg['numwant'] : $numwant);
    $compact_mode = ($tr_cfg['compact_always'] || !empty($compact));

    $SQL = "SELECT `ip`, `ipv6`, `port`
		    FROM tracker_new
		    WHERE torrent_id = '$torrent_id'
		    ORDER BY RAND()
		    LIMIT $limit";
    if ($res = $db->query($SQL) ){
        // Pack peers if compact mode
        if ($compact_mode) {
            $peerset = $peerset6 = '';
            while ($row = $res->fetch_assoc()){
                if (!empty($peer['ip'])) {
                    $peerset .= pack('Nn', ip2long(decode_ip($peer['ip'])), $peer['port']);
                }
                if (!empty($peer['ipv6'])) {
                    $peerset6 .= pack('H32n', $peer['ipv6'], $peer['port']);
                }
            }
        } else {
            $peerset = $peerset6 = array();
            while ($row = $res->fetch_assoc()){
                if (!empty($peer['ip'])) {
                    $peerset[] = array(
                        'ip' => decode_ip($peer['ip']),
                        'port' => intval($peer['port'])
                    );
                }
                if (!empty($peer['ipv6'])) {
                    $peerset6[] = array(
                        'ip' => decode_ip($peer['ipv6']),
                        'port' => intval($peer['port'])
                    );
                }
            }
        }
        $res->close();
    }


    $seeders = $peers = $leechers = 0;
    $SQL = "SELECT SUM(`seeder`) AS `seeders`, COUNT(*) AS `peers`
			FROM tracker_new
			WHERE torrent_id = $torrent_id;
			";
    if ($res = $db->query($SQL) ){
        $row = $res->fetch_assoc();
        $seeders = (int)$row['seeders'];
        $peers = (int)$row['peers'];
        $leechers = $peers - $seeders;
        $res->close();
    }

    $_sql = array();
    !empty($name) ? $_sql[] = "`name` = IF(`name` = '', '" . $name . "', `name`)" : FALSE;
    $size > 0 ? $_sql[] = "`size` = IF(`size` = 0, " . ((int)$size) . ", `size`)" : FALSE;
    !empty($comment) ? $_sql[] = "`comment` = IF(`comment` = '', '" . $comment . "', `comment`)" : FALSE;

    $SQL = "UPDATE tracker_stats_new SET
					seeders = $seeders,
					leechers = $leechers,
					update_time = " . TIMENOW . (sizeof($_sql) ? ", " . implode(", ", $_sql) : "") . "
			WHERE torrent_id = $torrent_id";
    unset($_sql);
    $db->query($SQL);

    // Generate output
    $ann_interval = $tr_cfg['announce_interval'] + mt_rand(0, 600);
    $output = array(
        'interval' => (int)$ann_interval,
        'min interval' => (int)$ann_interval,  // tracker config: min interval (sec?)
        'peers' => $peerset,
        'peers6' => $peerset6,
        'complete' => (int)$seeders,
        'incomplete' => (int)$leechers
    );

}

// Return data to client
echo bencode($output);

exit;