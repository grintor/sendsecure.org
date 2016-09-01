<?php
define("STRIPE_SK", "sk_test_KoH73vUJWl2AYjpsMCK0bRjN");

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

function getStripeUser($email) {
	$stripe_id = mysqli_fetch_array(sqlQuery("select stripe_id from users where email='$email'"));
	$stripe_id = $stripe_id['stripe_id'];
	
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

?>