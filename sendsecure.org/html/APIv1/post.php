<?php
require_once('../../resources/functions.php');

$returnArr = [];

$message_data = json_decode(file_get_contents('php://input'), true);

$auth_data = 'username=' . $message_data['smtpuser'] . '&password=' . $message_data['smtppass'];
$ch = curl_init('https://www.sendsecure.org/APIv1/auth/');                                                                      
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
curl_setopt($ch, CURLOPT_POSTFIELDS, $auth_data);                                                                  
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, 0);
$auth_result = curl_exec($ch); 
$auth_result = json_decode($auth_result, true); 
if ($auth_result['response']['error']){
	printJsonError(401 , 'UNAUTHORIZED');
}

$uniqid = uniqid('', true);		// this is also the salt (or iv)
$key = hash('sha256', openssl_random_pseudo_bytes(256));

if (isset($message_data['message']['html'])) {
	$message_data['message']['html'] = htmlspecialchars_decode($message_data['message']['html']);
}

$sqlResult = sqlQuery(sprintf(
	"INSERT INTO emails VALUES (
	UTC_TIMESTAMP,
	'%s',
	'%s',
	'%s',
	'%s',
	'%s',
	'%s',
	'%s',
	'%s',
	'%s',
	AES_ENCRYPT(compress('%s'), UNHEX('$key'), '$uniqid'),
	AES_ENCRYPT(compress('%s'), UNHEX('$key'), '$uniqid'),
	False,
	'%s')",
	mysqli_real_escape_string($mysqli, $message_data['smtpuser']),
	mysqli_real_escape_string($mysqli, json_encode($message_data['rcpttos'])),
	mysqli_real_escape_string($mysqli, $message_data['datetime']),
	mysqli_real_escape_string($mysqli, $message_data['subject']),
	mysqli_real_escape_string($mysqli, json_encode($message_data['headers'])),
	mysqli_real_escape_string($mysqli, json_encode($message_data['from'])),
	mysqli_real_escape_string($mysqli, json_encode($message_data['to'])),
	mysqli_real_escape_string($mysqli, json_encode($message_data['reply-to'])),
	mysqli_real_escape_string($mysqli, json_encode($message_data['cc'])),
	mysqli_real_escape_string($mysqli, json_encode($message_data['message'])),
	mysqli_real_escape_string($mysqli, json_encode($message_data['attachments'])),
	mysqli_real_escape_string($mysqli, $uniqid)
));
if (!$sqlResult) printJsonError(500 , 'Internal Server Error: Please try later or contact support. REF:5005');

$index = 0;
foreach($message_data['rcpttos'] as $rcptto) {
	$headers =  'From: "' . $message_data['from'][0]['name'] . '" <do-not-reply@sendsecure.org>' . "\r\n";
	$subject = '[SECURE] ' . $message_data['subject'];
	$message = 'You have a secure message. Click here to read it' . "\r\n" .
	'https://www.sendsecure.org/client/read.php?id=' . $uniqid . '&key=' . $key . '&index=' . $index;
	$additional = "-rbounce-$uniqid@sendsecure.org -ODeliveryMode=background";
	mail($rcptto, $subject, $message, $headers, $additional);
	$index++;
}

// TODO: handle errors
$returnArr['response']['code'] = 200;
$returnArr['response']['message'] = 'OK';
$returnArr['response']['error'] = false;
print json_encode($returnArr);
die;

?>