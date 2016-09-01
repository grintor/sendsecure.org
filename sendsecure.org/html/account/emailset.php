<?php
	session_start();	// if they were logged in...
	session_destroy();	// log them out
	require_once('../../resources/sqliConnect.php');
	$DBoldemail = mysqli_real_escape_string($mysqli, urldecode(strtolower($_GET['oldemail']))); // this is the url-encoded email forced lowercase and escaped
	$DBnewemail = mysqli_real_escape_string($mysqli, urldecode(strtolower($_GET['newemail']))); // so is this, but it's the email the are wanting to change it to
	$userkey = mysqli_real_escape_string($mysqli, urldecode($_GET['userkey']));
	do {
		if(!mysqli_fetch_array(sqlQuery("select email from users where email='$DBoldemail' and userkey='$userkey'")) || hash_hmac('sha256', urldecode($_GET['newemail']), 'SALT!~$') != $_GET['hmac']){ // if email/userkey or email/hamc mismatch
			$notify = "<font color='red'>url expired or invalid</font>";
			break;
		}
		$userkey = mt_rand(1000000000, 9999999999); // make current url expire
		sqlQuery("update users set email='$DBnewemail', userkey='$userkey' where email='$DBoldemail'", false);
		$notify = "<font color='green'>email updated </font><a href='login.php'>login</a>";
	} while(0);
?>

<html>
	<head>
		<title>update email</title>
	</head>
	<body>
		<?php print $notify ?>
	</body>
</html>