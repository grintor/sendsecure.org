<?php require('session.php'); // required on all pages in which you should be logged in to visit 
require_once('../../resources/functions.php');

$user = mysqli_fetch_array(sqlQuery("select * from users where email='$sessionuser'"));
$stripe_id = $user['stripe_id'];
$user = getStripeUser($stripe_id);
if (!isset($user['subscriptions']['data'][0]['status'])){
	$status = 'canceled';
} else {
	$status = $user['subscriptions']['data'][0]['status'];
}
if ($status == 'canceled') $status = 'inactive';
?>

<html>
	<head>
		<title>logged in</title>
	</head>
	<body>
		<div>Current Account Status: <?php print $status ?></div>
		<div><a href="profile.php">profile</a></div>
		<div><a href="billing.php">billing</a></div>
		<div><a href="subscriptions.php">subscriptions</a></div>
		<div><a href="logout.php">logout</a></div>
	</body>
</html>