<?php
require_once('SECRET.php');

function addressListHTML($addressArray, $delimer=',<br />') {
	$email = null;
	foreach($addressArray as $t) {
		if(!$email){ // if this is the first in the list of email's then we just print it
			if ($t['name']) { // if we have a name then we print it in quotes, then print the email in <>
				$email = '&quot;' . $t['name'] . '&quot;' . ' &lt;' . $t['email'] . '&gt;';
			} else { // if we don't have a name, we just print the email in <>
				$email = '&lt;' . $t['email'] . '&gt;';
			}
		} else { // if this is not the first in the list of email's then we print the delimer then print it
			if ($t['name']) { // if we have a name then we print it in quotes, then print the email in <>
				$email .= $delimer . '&quot;' . $t['name'] . '&quot;' . ' &lt;' . $t['email'] . '&gt;';
			} else { // if we don't have a name, we just print the email in <>
				$email .= $delimer . ' &lt;' . $t['email'] . '&gt;';
			}
			
		}
	}
	return $email;
}

function parseBounce($address){
	if (strpos($address, 'bounce-') !== False) {
		$Arr = explode('-', $address);
		if (strpos($Arr[1], '@')!== False) {
			$Arr = explode('@', $Arr[1]);
			return $Arr[0];
		}
	}
	return False;
}

function apiGetMessage($id, $key){
	$apiURL = 'https://www.sendsecure.org/APIv1?id=' . $id . '&key=' . $key;
	$context = stream_context_create(array(
		'http' => array('ignore_errors' => true),
	));
	$msgArr = json_decode(file_get_contents($apiURL, false, $context), true);
	if ($msgArr['response']['error']) {
		header("Location: error.php?error=" . $msgArr['response']['code']);
		die;
	}
	return $msgArr;
}

function indexToAddress($index, $emailArr){
	// get the email address from _rcpttos based on the index
	$address['email'] = $emailArr['rcpttos'][$index];
	$address['name']  = null;
	// get the name (if availble) corrasponding to that email address from _to
	foreach($emailArr['to'] as $t) {
		if ($t['email']==$address['email']){
			$address['name']=$t['name'];
		}
	}
	// also try looking for the name in _cc
	foreach($emailArr['cc'] as $t) {
		if ($t['email']==$address['email']){
			$address['name']=$t['name'];
		}
	}
	return $address;
}

function removeFromArray($array, $thing, $column) {
	$key = array_search($thing, array_column($array, $column));
	if($key !== FALSE){
		unset($array[$key]);
	}
	return $array;
}

function printJsonError($number, $message){
	$returnArr['response']['code'] = $number;
	$returnArr['response']['message'] = $message;
	$returnArr['response']['error'] = true;
	http_response_code($number);
	print json_encode($returnArr);
	die;
}

function registerWithStripe($userArr) {
	$description = urlencode($userArr['firstname'] . ' ' . $userArr['lastname']);
	$email = urlencode($userArr['email']);
	$fields_string = 'description=' . $description . '&email=' . $email;
	
	$ch = curl_init();
	curl_setopt($ch,CURLOPT_URL, 'https://api.stripe.com/v1/customers');
	curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_USERPWD, STRIPE_SK . ':');

	$result = curl_exec($ch); //execute post
	curl_close($ch);
	$result = json_decode($result, true);
	
	$DBstripe_id = $result['id'];
	$DBemail = $userArr['email'];
	sqlQuery("update users set stripe_id='$DBstripe_id' where email='$DBemail'", false);
}

function getStripeUser($stripe_id) {
	$ch = curl_init();
	curl_setopt($ch,CURLOPT_URL, "https://api.stripe.com/v1/customers/$stripe_id");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_USERPWD, STRIPE_SK . ':');

	$result = curl_exec($ch); //execute post
	curl_close($ch);
	return json_decode($result, true);
}

function updateCard($stripe_id, $number, $exp_month, $exp_year, $cvc) {
	
	$fields_string = "source[object]=card&source[exp_month]=$exp_month&source[exp_year]=$exp_year&source[number]=$number&source[cvc]=$cvc";

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "https://api.stripe.com/v1/customers/$stripe_id");
	curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_USERPWD, STRIPE_SK . ':');

	$result = curl_exec($ch); //execute post
	curl_close($ch);
	return json_decode($result, true);
}

function deleteCard($stripe_id) {
	$user = getStripeUser($stripe_id);
	if (!isset($user['sources']['data'][0]['id'])){
		$result['error']['message'] = 'No card info to remove';
		return $result;
	}
	$cardID = $user['sources']['data'][0]['id'];
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "https://api.stripe.com/v1/customers/$stripe_id/sources/$cardID");
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_USERPWD, STRIPE_SK . ':');

	$result = curl_exec($ch); //execute post
	curl_close($ch);
	return json_decode($result, true);
}

function addSubscription($stripe_id, $subscriptionEmail) {
	global $mysqli;
	
	// check if there are any subscriptions now. If so, we will be editing one below, otherwise we will be adding one below.
	$currentsubscriptions = mysqli_fetch_all(sqlQuery("select * from addresses where stripe_id='$stripe_id'"), MYSQLI_ASSOC);
	
	// put the new subscription in the database
	$DBaddress = mysqli_real_escape_string($mysqli, strtolower($subscriptionEmail));
	$DBaccount = uniqid();
	$DBpassword = uniqid();
	$DBuniqid = uniqid();
	sqlQuery("INSERT INTO addresses (address, account, password, stripe_id, uniqid) VALUES ('$DBaddress', '$DBaccount', '$DBpassword', '$stripe_id', '$DBuniqid')", false);

	
	if (!$currentsubscriptions) { // we are actaully creating a new subscription in this case
		return createSubscription($stripe_id);
	} else { // we will be editing a current subscription
		return updateSubscription($stripe_id);
	}

}

function subtractSubscription($stripe_id, $subscriptionID) {
	global $mysqli;
	$DBdelete = mysqli_real_escape_string($mysqli, $subscriptionID);
	sqlQuery("DELETE FROM addresses WHERE uniqid='$DBdelete' AND stripe_id = '$stripe_id'", false);
	updateSubscription($stripe_id);
}

function createSubscription($stripe_id) {
	$fields_string = "plan=ssm&customer=$stripe_id";

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "https://api.stripe.com/v1/subscriptions");
	curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_USERPWD, STRIPE_SK . ':');

	$result = curl_exec($ch); //execute post
	curl_close($ch);
	
	return json_decode($result, true);
}


// update stripe subscription with info from the database;
function updateSubscription($stripe_id) {
	$stripeUser = getStripeUser($stripe_id);
	
	$quantity = count(mysqli_fetch_all(sqlQuery("select * from addresses where stripe_id='$stripe_id'"), MYSQLI_ASSOC));
	if ($quantity == 0) {
		return deleteSubscription($stripe_id);
	}
	
	if ($quantity != 0 && !isset($stripeUser['subscriptions']['data'][0]['id'])){	// subscription doesn't exist but it should...
		createSubscription($stripe_id);												// maybe it was canceled on stripe? whatever, create one
		$stripeUser = getStripeUser($stripe_id);
	}
	$subscription = $stripeUser['subscriptions']['data'][0]['id'];
	
	$discount = ($quantity - 1) * 2; // two percent discount per added address after the first
	if ($discount >= 50) $discount = 50; // don't give more than 50% off
	$coupon = $discount . 'PercentOff';
	
	if ($discount) {
		$fields_string = "quantity=$quantity&coupon=$coupon";
	} else { // delete the discount
		$fields_string = "quantity=1";
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://api.stripe.com/v1/subscriptions/$subscription/discount");
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERPWD, STRIPE_SK . ':');
		curl_exec($ch);
	}

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "https://api.stripe.com/v1/subscriptions/$subscription");
	curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_USERPWD, STRIPE_SK . ':');

	$result = curl_exec($ch); //execute post
	curl_close($ch);
	return json_decode($result, true);
}

function deleteSubscription($stripe_id) {
	$return = null;
	$user = getStripeUser($stripe_id);
	foreach($user['subscriptions']['data'] as $subscription){
		$subscription = $subscription['id'];
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, "https://api.stripe.com/v1/subscriptions/$subscription");
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_USERPWD, STRIPE_SK . ':');

		$result = curl_exec($ch); //execute post
		curl_close($ch);
		$return[$subscription] = json_decode($result, true);
	}
	return $return;
}

// this function enables a user (sets active=true in the database)..
//		if there is a valid subscription in stripe for that user
// if not, it returns false
function reEnableUser($email){
	$user = mysqli_fetch_array(sqlQuery("select * from users where email='$email'"));
	if ($user['active']) return true;
	$stripe_id = $user['stripe_id'];
	$stripeUser = getStripeUser($stripe_id);
	if (!isset($stripeUser['subscriptions']['data'][0]['status'])){
		$status = 'canceled';
	} else {
		$status = $stripeUser['subscriptions']['data'][0]['status'];
	}
	if ($status != 'canceled') {
		sqlQuery("update users set active = TRUE where email='$email'");
		return true;
	} else {
		return false;
	}
}


?>