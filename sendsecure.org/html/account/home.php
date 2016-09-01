<?php require('session.php') // required on all pages in which you should be logged in to visit  ?>

<html>
	<head>
		<title>logged in</title>
	</head>
	<body>
		<div>You are logged in!</div>
		<div><a href="profile.php">profile</a></div>
		<div><a href="billing.php">billing</a></div>
		<div><a href="subscriptions.php">subscriptions</a></div>
		<div><a href="logout.php">logout</a></div>
	</body>
</html>