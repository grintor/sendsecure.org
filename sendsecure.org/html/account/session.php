<?php
	require_once('../../resources/sqliConnect.php');
	session_start();// Starting Session
	$sessionuser=$_SESSION['sessionuser'];
	
	if(!mysqli_fetch_array(sqlQuery("select email from users where email='$sessionuser'"))){
		header('Location: login.php'); // if they are not logged in, redirrect them to login page
	}
?>