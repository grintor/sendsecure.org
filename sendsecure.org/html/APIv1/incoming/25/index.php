<?php
require_once('../../../../resources/functions.php');

$headers = getallheaders ();
if (!isset($headers['Content-Type']) && $headers['Content-Type'] == 'application/json'){
	printJsonError(400 , 'Bad Request: Check the API documentation');
}

$message_data = json_decode(file_get_contents('php://input'), true);

print_r($message_data['from']);

?>