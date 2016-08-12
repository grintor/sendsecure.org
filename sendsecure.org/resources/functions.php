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
		print 'DB query failed';
		print ': ' . $mysqli->error;
		print "\nquery: " . $sqlRequest;
		exit;
	} else {
		return $sqlResult;
	}
}

function removeFromArray($array, $thing, $column) {
	$key = array_search($thing, array_column($array, $column));
	if($key !== FALSE){
		unset($array[$key]);
	}
	return $array;
}
?>