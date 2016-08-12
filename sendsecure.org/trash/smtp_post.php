<?php

require_once('./sqliConnect.php');

$message_data = json_decode(file_get_contents('php://input'), true);

//file_put_contents("message_data_str.txt", print_r($message_data, true));

$uniqid = uniqid('', true);		// this is also the salt (or iv)
$key = hash('sha256', openssl_random_pseudo_bytes(256));


$sqlRequest = sprintf(
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
	);

if (!$sqlResult = $mysqli->query($sqlRequest)) {
    print 'DB query failed';
	print ': ' . $mysqli->error;
	print 'query: ' . $sqlRequest;
    exit;
}

print 'mail posted';

$index = 0;
foreach($message_data['rcpttos'] as $rcptto) {
	$headers = 'From: "' . $message_data['from'][0]['name'] . '" <do-not-reply@sendsecure.org>';
	$subject = '[SECURE] ' . $message_data['subject'];
	$message = 'You have a secure message. Click here to read it' . "\r\n" .
	'https://www.sendsecure.org/read.php?id=' . $uniqid . '&key=' . $key . '&index=' . $index;
	mail($rcptto, $subject, $message, $headers);
	$index++;
}



?>