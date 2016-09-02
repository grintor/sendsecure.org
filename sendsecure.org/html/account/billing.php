<?php
	require('session.php');	// required on all pages in which you should be logged in to visit
	require_once('../../resources/functions.php');
	require_once('../../resources/sqliConnect.php');
	
	$user = mysqli_fetch_array(sqlQuery("select * from users where email='$sessionuser'"));
	$stripe_id = $user['stripe_id'];
	
	if (isset($_POST['change'])){
		do {
			$result = updateCard($stripe_id, $_POST['number'], $_POST['exp_month'], $_POST['exp_year'], $_POST['cvc']);
			if (isset($result['error'])) {
				$notify = '<font color="red">' . $result['error']['message'] . '</font>';
			} else {
				reEnableUser($sessionuser);
				$notify = '<font color="green">Success!</font>';
			}
		} while (0);
	}
	if (isset($_POST['remove'])){
		do {
			$result = deleteCard($stripe_id);
			if (isset($result['error'])) {
				$notify = '<font color="red">' . $result['error']['message'] . '</font>';
			} else {
				$notify = '<font color="green">Success!</font>';
			}
		} while (0);
	}
	
	$stripeUser = getStripeUser($stripe_id);
	
	if ($stripeUser['sources']['total_count'] === 0) {
		$cardnumber = null;
		$expmonth = null;
		$expyear = null;
		if (!isset($notify)) $notify = null;
	} else {
		$cardnumber = '***********' . $stripeUser['sources']['data'][0]['last4'];
		$expmonth = $stripeUser['sources']['data'][0]['exp_month'];
		$expyear = $stripeUser['sources']['data'][0]['exp_year'];
		if (!isset($notify)) $notify = 'Card type: ' . $stripeUser['sources']['data'][0]['brand'];
	}
	
?>

<html>
	<head>
		<title>billing</title>
	</head>
	<body>
		<form action="" method="post">
			<div><a href="home.php">back home</a></div>
			<div><b>Card Number</b></div>
			<span><input name="number" value="<?php print $cardnumber ?>" type="text"></span>
			<div><b>Exp Month / Year</b></div>
			<span><input name="exp_month" value="<?php print $expmonth ?>" type="number" min="1" max="12"></span>
			<span><input name="exp_year" value="<?php print $expyear ?>" type="number" min="2016" max="2099"></span>
			<div><b>security code</b></div>
			<span><input name="cvc" type="number" min="100" max="9999"></span>
			<div><input name="change" type="submit" value=" change "><input name="remove" type="submit" value=" remove "></div>
		</form>
		<div><?php print $notify ?></div>
	</body>
</html>