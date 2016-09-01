<?php
	require('session.php');	// required on all pages in which you should be logged in to visit
	require_once('../../resources/functions.php');
	
	$stripeUser = getStripeUser($_SESSION['sessionuser']);
	
	if ($stripeUser['sources']['total_count'] === 0) {
		$cardnumber = null;
		$expmonth = null;
		$expyear = null;
		$notify = null;
	} else {
		$cardnumber = '***********' . $stripeUser['sources']['data'][0]['last4'];
		$expmonth = $stripeUser['sources']['data'][0]['exp_month'];
		$expyear = $stripeUser['sources']['data'][0]['exp_year'];
		$notify = 'Card type: ' . $stripeUser['sources']['data'][0]['brand'];
	}
	
if (isset($_POST['submit'])){
		do {
			$result = updateCard($stripeUser['id'], $_POST['number'], $_POST['exp_month'], $_POST['exp_year'], $_POST['cvc']);
			if (isset($result['error'])) {
				$notify = '<font color="red">' . $result['error']['message'] . '</font>';
			} else {
				$notify = '<font color="green">Success!</font>';
			}
		} while (0);
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
			<div><input name="submit" type="submit" value=" change "></div>
		</form>
		<div><?php print $notify ?></div>
	</body>
</html>