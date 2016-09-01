<?php
	session_start();
	session_destroy();
	header("Location: index.php"); // redirrect them to the homepage
?>