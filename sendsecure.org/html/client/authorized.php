<?php
session_start();

if ($_POST['agree'] != 'true') {
	header('Location: authorize.php?' . $_SERVER['QUERY_STRING']);
} else {
	$_SESSION[$_GET['id']] = 'authorized';
	header('Location: read.php?' . $_SERVER['QUERY_STRING']);
}