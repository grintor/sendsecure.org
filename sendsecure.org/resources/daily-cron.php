<?php
require_once('functions.php');
require_once('sqliConnect.php');

$localUsers = mysqli_fetch_all(sqlQuery("select * from users WHERE active = TRUE"), MYSQLI_ASSOC);
foreach($localUsers as $localUser){
	sleep(1); // we don't want to trigger stripe's rate_limit_error 
	$stripe_id = $localUser['stripe_id'];
	$stripeUser = getStripeUser($stripe_id);
	if (!isset($stripeUser['subscriptions']['data'][0]['status'])){
		$status = 'canceled';
	} else {
		$status = $stripeUser['subscriptions']['data'][0]['status'];
	}
	if ($status == 'canceled') {
		$email = $localUser['email'];
		sqlQuery("update users set active = FALSE where email='$email'");
		mail($email,	'SendSecure.org Account Canceled', 'Your SendSecure.org Account has been canceled. ' . 
						'If this is not what you wanted, you can login to your account to recreate a subscription or update payment info',
						'From: noreply@sendsecure.org');
	}
	if ($status == 'past_due') {
		$email = $localUser['email'];
		mail($email, 	'SendSecure.org Past Due', 'Your SendSecure.org Account payment is past due.' .
						'To prevent service disruption, please log in and update your payment info.',
						'From: noreply@sendsecure.org');
	}
}
?>