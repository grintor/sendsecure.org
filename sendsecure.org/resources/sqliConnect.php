<?php
require_once('SECRET.php');

$mysqli = mysqli_init();

if (!$mysqli) {
    trigger_error('mysqli_init failed');
	
	} elseif (!$mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, 600)){
		trigger_error('Setting MYSQLI_OPT_CONNECT_TIMEOUT failed');
		$mysqli = false;
		
		} elseif (!$mysqli->real_connect('127.0.0.1', DBUSER, DBPASS, 'sendsecure')){
			trigger_error('$mysqli->real_connect failed');
			$mysqli = false;
			
			} elseif (!$sqlResult = $mysqli->query('SET @@session.block_encryption_mode = "aes-256-cbc"')) {
				trigger_error('Failed to initilize encryption');
				$mysqli = false;
			}
			
function sqlQuery($query){
	global $mysqli;
	if (!$sqlResult = $mysqli->query($query)) {
		trigger_error('DB query failed: ' . $mysqli->error . "\nquery: " . $query);
		return false;
	} else {
		return $sqlResult;
	}
}

?>
