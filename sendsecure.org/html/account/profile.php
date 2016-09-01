<?php
	require('session.php');	// required on all pages in which you should be logged in to visit
	require_once('../../resources/sqliConnect.php');
	$user = mysqli_fetch_array(sqlQuery("select * from users where email='$sessionuser'"));
	if (isset($_POST['submit'])){
		do {
			if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
				$notify = "<font color='red'>email address not valid</font>";
				break;
			}
			if (!ctype_alpha($_POST['firstname'])) {
				$notify = "<font color='red'>first name not valid</font>";
				break;
			}
			if (!ctype_alpha($_POST['lastname'])) {
				$notify = "<font color='red'>last name not valid</font>";
				break;
			}
			if (!ctype_print($_POST['password1'])) {
				$notify = "<font color='red'>password is not valid</font>";
				break;
			}
			if ($_POST['password1'] != $_POST['password2']) {
				$notify = "<font color='red'>passwords do not match</font>";
				break;
			}
			if (strlen($_POST['password1']) < 6) {
				$notify = "<font color='red'>password is too short</font>";
				break;
			}
			if ($_POST['password1'] == "xxxxxxxx") {		// xxxxxxxx is the default value in the html below -- so if it's not that, they are requesting to change it
				$DBpassword = $user["password"];
			} else {
				$DBpassword = sha1($_POST['password1']);	// no need to escape, we dictate the format of the password
			}
			$DBemail = $user['email'];	// not need to escape, we pulled this from our database
			$DBfirstname = mysqli_real_escape_string($mysqli, ucfirst($_POST['firstname']));
			$DBlastname = mysqli_real_escape_string($mysqli, ucfirst($_POST['lastname']));
			sqlQuery("update users set password='$DBpassword', firstname='$DBfirstname', lastname='$DBlastname' where email='$DBemail'", false);
			$notify = "<font color='green'>profile updated</font>";
			if (strtolower($_POST['email']) != $user["email"]) { // user has requested to change email. we need to send verification
				$userkey = mt_rand(1000000000, 9999999999); // used to verify user is who they say
				$HMAC = hash_hmac('sha256', $_POST['email'] , 'SALT!~$'); // used to ensure that the url-encoded new address we are emailing has not been manipulated by the user
				$domain = $_SERVER['HTTP_HOST'];
				$subdir = dirname($_SERVER['REQUEST_URI']);
				$subject = $domain . " email change";
				$headers  = "MIME-Version: 1.0\r\nContent-type: text/html; charset=iso-8859-1\r\nFrom: noreply@$domain"; // minimum headers required for HTML-Style email
				$message =	"<html>
								<body>
									click
										<a href='$domain/$subdir/emailset.php?oldemail=".urlencode($user['email'])."&newemail=".urlencode($_POST['email'])."&userkey=$userkey&hmac=$HMAC'>
											here
										</a>
									to complete change of email
								</body>
							</html>";
				mail($_POST['email'], $subject, $message, $headers);
				sqlQuery("update users set userkey='$userkey' where email='$DBemail'", false);
				$notify = "<font color='green'>change of email verification sent to " . $_POST['email'] . "</font>";
			}
		} while (0);
	} else {
		$notify = null;
	}
	$user = mysqli_fetch_array(sqlQuery("select * from users where email='$sessionuser'")); // query again, in case the info was updated
?>

<html>
	<head>
		<title>profile</title>
	</head>
	<body>
		<form action="" method="post">
			<div><a href="home.php">back home</a></div>
			<div><b>name</b></div>
			<span><input name="firstname" value="<?php print $user['firstname'] ?>" type="text"></span>
			<span><input name="lastname" value="<?php print $user['lastname'] ?>" type="text"></span>
			<div><b>email address</b></div>
			<div><input name="email" value="<?php print $user['email'] ?>" type="text"></div>
			<div><b>password</b></div>
			<span><input name="password1" value="xxxxxxxx" type="password"></span>
			<span><input name="password2" value="xxxxxxxx" type="password"></span>
			<div><input name="submit" type="submit" value=" change "></div>
		</form>
		<div><?php print $notify ?></div>
	</body>
</html>