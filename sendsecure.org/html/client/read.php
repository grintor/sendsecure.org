<?php
define("RFC2822", "D, d M Y H:i:s O");
header("content-type: text/html; charset=UTF-8");
require_once('../../resources/functions.php');
require_once('../../resources/smarty-3.1.30/Smarty.class.php');
$smarty = new Smarty;
$smarty->setCompileDir('/tmp/smarty-templates_c');
$smarty->setCacheDir('/tmp/smarty-cache');
$smarty->setTemplateDir('../../resources/smarty-template_dir');

session_start();
if (!isset($_SESSION[$_GET['id']]) || $_SESSION[$_GET['id']] != 'authorized') {
	header('Location: authorize.php?' . $_SERVER['QUERY_STRING']);
}

$emailArr = apiGetMessage($_GET['id'], $_GET['key']);


if(isset($emailArr['message']['html'])){
	$message = htmlspecialchars_decode($emailArr['message']['html']);
} elseif (isset($emailArr['message']['text'])) {
	$message = $emailArr['message']['text'];
} else {
	$message = 'NO MSG';
}


$from = addressListHTML($emailArr['from']);
$to = addressListHTML($emailArr['to']);
if (!$to) {$to = '(Undisclosed Recipients)';}

$cc = $emailArr['cc'];
if ($cc){
	$cc = addressListHTML($emailArr['cc']);
	$cc = "<p><span class = 'vars'>CC:</span><span>" . $cc . "</span></p>";
} else {
	$cc = null;	// if $cc is an empty array, make it null (smarty hates empty arrays)
}


if ($emailArr['replyto']){
	$replyTo = addressListHTML($emailArr['replyto']);
	$replyTo = "<p><span class = 'vars'>Reply To:</span><span>" . $replyTo . "</span></p>";
} else {
	$replyTo = null; // if $replyTo is an empty array, make it null (smarty hates empty arrays)
}


$attachmentArr = $emailArr['attachments'];
$attachments = null;
$index = 0;
foreach($attachmentArr as $f) {
	// the part that is used in each instance of the list
	$x = '<a href="' . $f['url'] . '"><font color="red">' . $f['filename'] . '</font></a>';
	$index++;
	if(!$attachments){
		// the part that is only used for the first element of the list
		$attachments = '<p><span class = "vars">Attachments:</span><span>' . $x;
	} else {
		// the part that is used for each subsequent element of the list
		$attachments .= ' | ' . $x;
	}
}
// the part that is appended at the end of the list
if($attachments) $attachments .= '</span></p>';

$date = $emailArr['datetime'];
$date = strtotime($date);
$date = date(RFC2822, $date);

if (strpos($_SERVER['HTTP_USER_AGENT'], 'Mobi') !== false) {
	$platform = 'mobile';
} else {
	$platform = 'desktop';
}

$smarty->assign('QUERY_STRING', $_SERVER['QUERY_STRING']);
$smarty->assign('subject', $emailArr['subject']);
$smarty->assign('attachments', $attachments);
$smarty->assign('from', $from);
$smarty->assign('date', $date);
$smarty->assign('to', $to);
$smarty->assign('platform', $platform);
$smarty->assign('replyTo', $replyTo);
$smarty->assign('cc', $cc);
$smarty->assign('message', $message);
$smarty->display('read.tpl');

?>