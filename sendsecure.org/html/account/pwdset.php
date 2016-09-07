<?php
	require_once('../../resources/sqliConnect.php');
	require_once('../../resources/functions.php');
	$notify = null;
	if (!$_GET["email"] || !$_GET["userkey"]) {	// if they are missing any info...
		header("Location: index.php"); 				// they shouldn't be here, show them the homepage
	} else {
		$DBemail = mysqli_real_escape_string($mysqli, urldecode($_GET["email"]));
		$DBuserkey = mysqli_real_escape_string($mysqli, $_GET["userkey"]);
		$userArr = mysqli_fetch_array(sqlQuery("select * from users where email='$DBemail' and userkey='$DBuserkey'"));
		if (!$userArr) {	// test that the reset key matches current one for that email.
			print "<font color='red'>url expired or invalid</font>";
			die;
		}
	}
	if (isset($_POST['submit'])){
		do {
			if ($_POST['password1'] != $_POST['password2']) {
				$notify = "<font color='red'>passwords do not match</font>";
				break;
			}
			if (!ctype_print($_POST['password1'])) {
				$notify = "<font color='red'>password is not valid</font>";
				break;
			}
			if (strlen($_POST['password1']) < 6) {
				$notify = "<font color='red'>password is too short</font>";
				break;
			}
			if ($userArr['password'] == Null){ // this is their first time registering,
				registerWithStripe($userArr);  // so sign them up in stripe
			}
			$DBpassword = sha1($_POST['password1']);	// no need to escape, we dictate the format of the password
			$DBuserkey = mt_rand(1000000000, 9999999999); // used to make current url expire
			sqlQuery("update users set password='$DBpassword', userkey='$DBuserkey' where email='$DBemail'", false);
			header('Location: login.php'); // success, now go to login page
		} while (0);
	}
?>

<html>
	<head>
		<title>edit password</title>
	</head>
	<body>
		<form action="" method="post">
			<div><b>enter desired password</b></div>
			<span><input name="password1" placeholder=" password" type="password"></div>
			<span><input name="password2" placeholder=" confirm" type="password"></div>
			<div><input name="submit" type="submit" value=" submit "></div>
		</form>
		<div><?php print $notify ?></div>
	</body>
</html>