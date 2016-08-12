<?php
require_once('../../resources/functions.php');
require_once('../../resources/htmlpurifier-4.8.0-standalone/HTMLPurifier.standalone.php');
$HTMLPurifier = new HTMLPurifier();


$sqlResult = mysqli_fetch_array(sqlQuery(sprintf("SELECT
	uniqid,
	_to,
	_from,
	_datetime,
	_subject,
	_rcpttos,
	_replyto,
	_cc,
	UNCOMPRESS(AES_DECRYPT(_message, UNHEX('%s'), '%s')) AS _message,
	UNCOMPRESS(AES_DECRYPT(_attachments, UNHEX('%s'), '%s')) AS _attachments
	FROM emails WHERE uniqid = '%s'",
	mysqli_real_escape_string($mysqli, $_GET['key']),
	mysqli_real_escape_string($mysqli, $_GET['id']),
	mysqli_real_escape_string($mysqli, $_GET['key']),
	mysqli_real_escape_string($mysqli, $_GET['id']),
	mysqli_real_escape_string($mysqli, $_GET['id'])
)));

if (!$sqlResult['_message']) die;

$emailArr = [];
$emailArr['to'] = json_decode($sqlResult['_to'], true);
$emailArr['from'] = json_decode($sqlResult['_from'], true);
$emailArr['datetime'] = $sqlResult['_datetime'];
$emailArr['subject'] = $sqlResult['_subject'];
$emailArr['replyto'] = json_decode($sqlResult['_replyto'], true);
$emailArr['cc'] = json_decode($sqlResult['_cc'], true);
$emailArr['rcpttos'] = json_decode($sqlResult['_rcpttos'], true);

$emailArr['attachments'] = [];
$attachmentArr = json_decode($sqlResult['_attachments'], true); 
$i = 0;
foreach($attachmentArr as $f) {
	$emailArr['attachments'][$i]['filename'] = 	$f['filename'];
	$emailArr['attachments'][$i]['url'] = 'https://www.sendsecure.org/APIv1/download/?id='. $_GET['id'] .'&index=' . $i . '&key=' . $_GET['key'];
}

$emailArr['message']['html'] = null;
$emailArr['message']['text'] = null;
$messageArr = json_decode($sqlResult['_message'], true); 
if (isset($messageArr['html'])){
	$emailArr['message']['html'] = htmlspecialchars($HTMLPurifier->purify($messageArr['html']));
}
if (isset($messageArr['text'])){
	$emailArr['message']['text'] = $messageArr['text'];
}


print json_encode($emailArr);

?>