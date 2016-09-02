<?php
require('session.php'); // required on all pages in which you should be logged in to visit
require_once('../../resources/sqliConnect.php');
require_once('../../resources/functions.php');

$notify = null;

$user = mysqli_fetch_array(sqlQuery("select * from users where email='$sessionuser'"));
$stripe_id = $user['stripe_id'];

if (isset($_POST['submit'])){
	do{
		if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
			$notify = "<font color='red'>email address not valid</font>";
			break;
		}
		addSubscription($stripe_id, $_POST['email']);
		reEnableUser($sessionuser);
	}while(0);
	
}

if (isset($_GET['delete'])){
	subtractSubscription($stripe_id, $_GET['delete']);
}

$addresses = mysqli_fetch_all(sqlQuery("select * from addresses where stripe_id='$stripe_id'"), MYSQLI_ASSOC);

$HTMLaddresslist = null;
foreach ($addresses as $address) {
	$HTMLaddresslist .= '<tr>
							<td>' . $address['address'] . '</td>
							<td>' . $address['account'] . '</td>
							<td>' . $address['password'] . '</td>
							<td><a href="?delete=' . $address['uniqid'] . '">delete</a></td>
						</tr>';
}

?>

<html>
	<head>
		<title>logged in</title>
		<style>
			table {
				font-family: arial, sans-serif;
				border-collapse: collapse;
			}

			td, th {
				border: 1px solid #dddddd;
				text-align: left;
				padding: 4px;
			}

			tr:nth-child(even) {
				background-color: #dddddd;
			}
		</style>
	</head>
	<body>
		<div>Your account has <?php print count($addresses) ?> subscriptions:</div>
		<div><a href="home.php">back home</a></div>
		<table>
			<tr><th>Address</th><th>SMTP User</th><th>SMTP Pass</th><th>&nbsp;</th></tr>
			<?php print $HTMLaddresslist ?>
		</table>
		<form method="post">
		<input type='text' name='email'><input type='submit' name="submit" value="Add">
		</form>
		<div><?php print $notify ?></div>
	</body>
</html>