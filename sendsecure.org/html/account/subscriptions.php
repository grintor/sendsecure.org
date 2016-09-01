<?php

require('session.php'); // required on all pages in which you should be logged in to visit 
$user = mysqli_fetch_array(sqlQuery("select * from users where email='$sessionuser'"));
$stripe_id = $user['stripe_id'];
$addresses = mysqli_fetch_all(sqlQuery("select * from addresses where stripe_id='$stripe_id'"));
print_r($addresses);


?>

<html>
	<head>
		<title>logged in</title>
	</head>
	<body>
		<div>Your account has <?php print count($addresses) ?> subscriptions:</div>
		<div><a href="profile.php">profile</a></div>
		<div><a href="billing.php">billing</a></div>
		<div><a href="logout.php">logout</a></div>
	</body>
</html>