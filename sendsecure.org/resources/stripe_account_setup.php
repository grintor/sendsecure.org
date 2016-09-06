<?php
// php -f stripe_account_setup.php


require_once('SECRET.php');

function createCoupons() {

	for ($discount = 2; $discount <= 50; $discount +=2 ){
		$coupon = $discount . 'PercentOff';
		$fields_string = "id=$coupon&duration=forever&percent_off=$discount";

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://api.stripe.com/v1/coupons");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERPWD, STRIPE_SK . ':');

		$result = curl_exec($ch); //execute post
		curl_close($ch);
		$return[$coupon] = json_decode($result, true);
	}
	return $return;
}

function createPlan(){
	$fields_string = "name=SendSecure_Monthly&amount=999&interval=month&currency=usd&id=ssm&statement_descriptor=SendSecure.org&trial_period_days=30";

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "https://api.stripe.com/v1/plans");
	curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_USERPWD, STRIPE_SK . ':');

	$result = curl_exec($ch); //execute post
	curl_close($ch);
	return json_decode($result, true);
}

print_r(createPlan());
print_r(createCoupons());
?>