<?php

$mysqli = mysqli_init();
if (!$mysqli) {
    die('mysqli_init failed');
}

if (!$mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, 600)) {
    die('Setting MYSQLI_OPT_CONNECT_TIMEOUT failed');
}

if (!$mysqli->real_connect('127.0.0.1', 'user', 'pass', 'sendsecure')) {
    die('Connect Error (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
}

if (!$sqlResult = $mysqli->query('SET @@session.block_encryption_mode = "aes-256-cbc"')) {
    die('Failed to initilize encryption');
}

?>
