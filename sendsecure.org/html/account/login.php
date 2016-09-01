<?php
	require_once('../../resources/sqliConnect.php');
	session_start();
	if (isset($_POST['submit'])){
		sleep(1);	// mitigate brute-force attack
		$email = strtolower($_POST['email']);
		$email = mysqli_real_escape_string($mysqli, $email);
		$password = sha1($_POST['password']);
		if (mysqli_fetch_array(sqlQuery("select * from users where password='$password' AND email='$email'"))) {
			$_SESSION['sessionuser'] = $email;
			header("location: home.php"); // Redirecting To Other Page
		} else {
			$notify = "<font color='red'>Invalid email or password. Try again.</font>";
		}
	} else {
		$_POST['email'] = null;
		$notify = null;
	}
?>

<html>
	<head>
		<title>log in</title>
	</head>
	<body>
		<form action="" method="post">
			<div><input name="email" placeholder=" email" value="<?php print $_POST['email']?>" type="text"></div>
			<div><input name="password" placeholder=" password" type="password"></div>
			<div><input name="submit" type="submit" value=" login "></div>
		</form>
		<div><?php print $notify ?></div>
		<div><a href="forgotpwd.php">forgot password</a></div>
	</body>
</html>