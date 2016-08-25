<?php
header("content-type: text/html; charset=UTF-8");
require_once('../../resources/sqliConnect.php');
require_once('../../resources/functions.php');

if (isset($_GET['key']) && isset($_GET['id'])){
	require_once('get.php');
}

$headers = getallheaders ();
if (isset($headers['Content-Type']) && $headers['Content-Type'] == 'application/json'){
	require_once('post.php');
}

http_response_code(400);
printJsonError(400 , 'Bad Request: Check the API documentation');

?>