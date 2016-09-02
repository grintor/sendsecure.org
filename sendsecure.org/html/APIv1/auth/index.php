<?php
sleep(1);

require_once('../../../resources/functions.php');
require_once('../../../resources/sqliConnect.php');

if (!array_key_exists('username', $_POST) || !array_key_exists('password', $_POST)){
	printJsonError(400 , 'Bad Request: Invalid POST. Check the API documentation');
}
$sqladdress = mysqli_fetch_array(sqlQuery(sprintf("SELECT * FROM addresses WHERE account = '%s' AND password = '%s'",
	mysqli_real_escape_string($mysqli, $_POST['username']),
	mysqli_real_escape_string($mysqli, $_POST['password'])
)));

if ($sqladdress) {
	$stripe_id = $sqladdress['stripe_id'];
	$sqluser = mysqli_fetch_array(sqlQuery("SELECT * FROM users WHERE stripe_id = '$stripe_id'"));
	if ($sqluser['active']) {
		http_response_code(202);
		$returnArr['address'] = $sqladdress['address'];
		$returnArr['response']['code'] = 202;
		$returnArr['response']['message'] = 'OK';
		$returnArr['response']['error'] = false;
		print json_encode($returnArr);
	}

} else {
	printJsonError(401 , 'UNAUTHORIZED');
}


?>