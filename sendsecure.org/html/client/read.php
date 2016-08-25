<?php
define("RFC2822", "D, d M Y H:i:s O");
header("content-type: text/html; charset=UTF-8");
require_once('../../resources/functions.php');
require_once('../../resources/smarty-3.1.30/Smarty.class.php');
$smarty = new Smarty;
$smarty->setCompileDir('/tmp/smarty-templates_c');
$smarty->setCacheDir('/tmp/smarty-cache');
$smarty->setTemplateDir('../../resources/smarty-template_dir');

$apiURL = 'https://www.sendsecure.org/APIv1?id=' . $_GET['id'] . '&key=' . $_GET['key'];
$emailArr = json_decode(file_get_contents($apiURL), true);

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
	$x = '<a href="' . $f['url'] . '">' . $f['filename'] . '</a>';
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
	$css = 'read.mobile.css';
} else {
	$css = 'read.desktop.css';
}

$smarty->assign('id', $_GET['id']);
$smarty->assign('index', $_GET['index']);
$smarty->assign('key', $_GET['key']);
$smarty->assign('subject', $emailArr['subject']);
$smarty->assign('attachments', $attachments);
$smarty->assign('from', $from);
$smarty->assign('date', $date);
$smarty->assign('to', $to);
$smarty->assign('css', $css);
$smarty->assign('replyTo', $replyTo);
$smarty->assign('cc', $cc);
$smarty->assign('message', $message);
$smarty->display('read.tpl');

	
?>