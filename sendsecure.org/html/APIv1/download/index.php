<?php
require_once('../../../resources/sqliConnect.php');

$sqlRequest = sprintf(
		"SELECT UNCOMPRESS(AES_DECRYPT(_attachments, UNHEX('%s'), '%s')) AS _attachments FROM emails WHERE uniqid = '%s'",
		mysqli_real_escape_string($mysqli, $_GET['key']),
		mysqli_real_escape_string($mysqli, $_GET['id']),
		mysqli_real_escape_string($mysqli, $_GET['id'])
);



if (!$sqlResult = $mysqli->query($sqlRequest)) {
    print 'DB query failed';
	print ': ' . $mysqli->error;
	print "\nquery: " . $sqlRequest;
    exit;
}

$sqlResult = mysqli_fetch_array($sqlResult, MYSQL_ASSOC);

$attachments = json_decode($sqlResult['_attachments'], true);

$attachment = base64_decode($attachments[$_GET['index']]['content']);

header("Content-Disposition: attachment; filename=\"" . $attachments[$_GET['index']]['filename'] . "\"");
header("Content-Type: application/octet-stream");
header("Content-Length: " . strlen($attachment));
header("Connection: close");

print $attachment;

?>