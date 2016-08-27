<?php

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


function sqlQuery($query){
	global $mysqli;
	if (!$sqlResult = $mysqli->query($query)) {
		trigger_error('DB query failed: ' . $mysqli->error . "\nquery: " . $query);
		return false;
	} else {
		return $sqlResult;
	}
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



?>