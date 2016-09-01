<?php
	require_once('../../resources/sqliConnect.php');

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
			$userkey = mt_rand(1000000000, 9999999999);  // this is used to verify the url we are emailing is valid
			$DBemail = mysqli_real_escape_string($mysqli, strtolower($_POST['email']));
			$DBfirstname = mysqli_real_escape_string($mysqli, ucfirst($_POST['firstname']));
			$DBlastname = mysqli_real_escape_string($mysqli, ucfirst($_POST['lastname']));
			$domain = $_SERVER['HTTP_HOST'];
			$subdir = dirname($_SERVER['REQUEST_URI']);
			$emlSubject = $domain . " registration";
			$emlHeaders  = "MIME-Version: 1.0\r\nContent-type: text/html; charset=iso-8859-1\r\nFrom: noreply@$domain"; // minimum headers required for HTML-Style email
			$emlMessage =	"<html>
								<body>
									click 
										<a href='$domain/$subdir/pwdset.php?email=".urlencode($_POST['email'])."&userkey=$userkey'>
											here
										</a>
									to complete registration
								</body>
							</html>";
			if (mysqli_fetch_array(sqlQuery("select * from users where email='$DBemail'"))) {
				$notify = "<font color='red'>email is already registered</font>";
				break;
			}
			if (!sqlQuery("insert into users (email, userkey, firstname, lastname) values ('$DBemail', '$userkey', '$DBfirstname', '$DBlastname')", false)){
				$notify = "<font color='red'>an error occured, please try again later</font>";
				break;
			}
			mail($_POST['email'], $emlSubject, $emlMessage, $emlHeaders);
			$notify = "<font color='green'>registration email sent</font>";
		} while (0);
	} else {
		$_POST['firstname'] = null;
		$_POST['lastname'] = null;
		$_POST['email'] = null;
		$notify = null;
	}
?>

<html>
	<head>
		<title>register</title>
	</head>
	<body>
		<form action="" method="post">
			<div><b>name</b></div>
			<span><input name="firstname" placeholder=" first" value="<?php print $_POST['firstname']?>" type="text"></span>
			<span><input name="lastname" placeholder=" last" value="<?php print $_POST['lastname']?>" type="text"></span>
			<div><b>email address</b></div>
			<div><input name="email" placeholder=" you@example.com" value="<?php print $_POST['email']?>" type="text"></div>
			<div><input name="submit" type="submit" value=" register "></div>
		</form>
		<div><?php print $notify ?></div>
	</body>
</html>