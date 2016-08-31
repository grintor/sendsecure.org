<?php
require_once('../../../../resources/functions.php');
require_once('../../../../resources/sqliConnect.php');

$headers = getallheaders ();
if (!isset($headers['Content-Type']) || $headers['Content-Type'] != 'application/json'){
	printJsonError(400 , 'Bad Request: Content-Type != application/json: Check the API documentation');
}

$message_data = json_decode(file_get_contents('php://input'), true);

$bounceKey = parseBounce($message_data['to'][0]['email']);
if ($bounceKey) {
	$sqlResult = sqlQuery(sprintf("SELECT _from, bounced FROM emails WHERE uniqid = '%s'",
		mysqli_real_escape_string($mysqli, $bounceKey)
	));
	if (!$sqlResult) printJsonError(500 , 'Internal Server Error: Please try later or contact support. REF:5003'); // no response from the query, sql died during the request?
	$sqlResult = mysqli_fetch_array($sqlResult);
	
	if ($sqlResult['bounced']){
		printJsonError(403 , 'Forbidden: This one-time bounce address has already been consumed');
	}
	
	sqlQuery(sprintf("UPDATE emails SET bounced = True WHERE uniqid = '%s'",
		mysqli_real_escape_string($mysqli, $bounceKey)
	));
	
	$to = json_decode($sqlResult['_from'], true);
	$headers =  'From: "Mail Subsystem" <do-not-reply@sendsecure.org>' . "\r\n";
	$subject = '[SECURE] Delivery Failed';
	$message = $message_data['message'][0]['content'];
	$additional = "-ODeliveryMode=background";
	mail($to[0]['email'], $subject, $message, $headers, $additional);
}

$prefix = explode('@', $message_data['to'][0]['email']);

$sqlResult = sqlQuery(sprintf("SELECT address FROM addresses WHERE uniqid = '%s'",
	mysqli_real_escape_string($mysqli, $prefix[0])
));

$sqlResult = mysqli_fetch_array($sqlResult);

if ($sqlResult) {
	$to = $sqlResult['address'];
	$headers =  'From: "Mail Subsystem" <do-not-reply@sendsecure.org>' . "\r\n";
	$subject = 'FWD: ' . $message_data['subject'];
	$message = 'MGG FROM: ' . $message_data['from'][0]['email'] . "\r\n" . $message_data['message'][0]['content'];
	$additional = "-ODeliveryMode=background";
	if (strpos($to, 'sendsecure.org')== False) { // we are not going to forward mail to ourself
		mail($to, $subject, $message, $headers, $additional);
	} else {
		printJsonError(403 , 'Forbidden: Cannot forward mail to self');
	}
}

$returnArr['response']['code'] = 200;
$returnArr['response']['message'] = 'OK';
$returnArr['response']['error'] = false;
print json_encode($returnArr);
die;

?>