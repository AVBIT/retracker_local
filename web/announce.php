<?php
/**
 * ----------------------------------------------------------------------------
 *                              ANNOUNCER
 * ----------------------------------------------------------------------------
 * Modified by Viacheslav Avramenko aka Lordz (avbitinfo@gmail.com)
 * Created on 02.03.2016. Last modified on 24.10.2016
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

	$db->query("DELETE FROM tracker WHERE update_time < $peer_expire_time;");
	die();
}

// Recover info_hash
if (isset($_GET['?info_hash']) && !isset($_GET['info_hash'])) {
	$_GET['info_hash'] = $_GET['?info_hash'];
}

// Input var names
$input_vars_str = array( 'info_hash', 'event',); // String
$input_vars_num = array( 'port',); // Numeric

// Init received data
// String
foreach ($input_vars_str as $var_name){
	$$var_name = isset($_GET[$var_name]) ? (string) $_GET[$var_name] : null;
}
// Numeric
foreach ($input_vars_num as $var_name){
	$$var_name = isset($_GET[$var_name]) ? (float) $_GET[$var_name] : null;
}

// Verify required request params (info_hash, port)
if (!isset($info_hash) || strlen($info_hash) != 20) msg_die('Invalid info_hash');
if (!isset($port) || $port < 0 || $port > 0xFFFF) msg_die('Invalid port');

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
if (!verify_ip($ip)) msg_die("Invalid IP: $ip");

// Convert IP to HEX format
$ip_sql = encode_ip($ip);


// --------------------------------------------------------------------
// Start announcer
// --------------------------------------------------------------------

$info_hash_sql = $db->real_escape_string($info_hash);

// Stopped event
if ($event === 'stopped'){
	$db->query("DELETE FROM tracker WHERE info_hash = '$info_hash_sql' AND ip = '$ip_sql' AND port = $port;");
	die();
}

// Update peer info
$db->query("REPLACE INTO tracker (info_hash, ip, port, update_time) VALUES ('$info_hash_sql', '$ip_sql', $port, ". TIMENOW .");");


// PREPARATION OUTPUT
// Retrieve peers
$peers = '';
$ann_interval = $tr_cfg['announce_interval'] + mt_rand(0, 600);
$SQL = "SELECT ip, port
		FROM tracker
		WHERE info_hash = '$info_hash_sql'
		ORDER BY RAND()
		LIMIT ". (int) $tr_cfg['numwant'] .";";

if ($res = $db->query($SQL) ){
	while ($peer = $res->fetch_assoc()){
		$peers .= pack('Nn', ip2long(decode_ip($peer['ip'])), $peer['port']);
	}
	$res->close();
}

$output = array(
	'interval'     => (int) $ann_interval,
	'min interval' => (int) $ann_interval,
	'peers'        => $peers,
);

// Return data to client
echo bencode($output);

exit;