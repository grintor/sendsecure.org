<?php
	require_once('../../resources/sqliConnect.php');
	if (isset($_POST['submit'])){
		do {
			if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
				$notify = "<font color='red'>email address not valid</font>";
				break;
			}
			$DBemail = mysqli_real_escape_string($mysqli, $_POST['email']);
			if (!mysqli_fetch_array(sqlQuery("select * from users where email='$DBemail'"))) {
				$notify = "<font color='red'>email not registered</font>";
				break;
			}
			$userkey = mt_rand(1000000000, 9999999999); // used to verify url is valid and allow us to expire it
			$domain = $_SERVER['HTTP_HOST'];
			$subdir = dirname($_SERVER['REQUEST_URI']);
			$emlSubject = $domain . " password change";
			$emlHeaders  = "MIME-Version: 1.0\r\nContent-type: text/html; charset=iso-8859-1\r\nFrom: noreply@$domain"; // minimum headers required for HTML-Style email
			$emlMessage =	"<html>
							<body>
								click 
									<a href='$domain/$subdir/pwdset.php?email=".urlencode($_POST['email'])."&userkey=$userkey'>
										here
									</a>
								to reset your password
							</body>
						</html>";
			mail($_POST['email'], $emlSubject, $emlMessage, $emlHeaders);
			sqlQuery("update users set userkey='$userkey' where email='$DBemail'", false); // invalidating the url
			$notify = "<font color='green'>password reset link sent to " . $_POST['email'] . "</font>";
		} while (0);
	} else {
		$_POST['email'] = null;
		$notify = null;
		
	}
?>

<html>
	<head>
		<title>forgot password</title>
	</head>
	<body>
		<form action="" method="post">
			<div><b>email address</b></div>
			<div><input name="email" placeholder=" you@example.com" value="<?php print $_POST['email']?>" type="text"></div>
			<div><input name="submit" type="submit" value=" submit "></div>
		</form>
		<div><?php print $notify ?></div>
	</body>
</html>