<?php
define("RFC2822", "D, d M Y H:i:s O");
header("content-type: text/html; charset=UTF-8");

require_once('../../resources/functions.php');
require_once('../../resources/html2text-0.3.4/html2text.php');
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

// get the email address from _rcpttos based on the index
$from[0] = indexToAddress($_GET['index'], $emailArr);

$subject = 'RE: ' . $emailArr['subject'];

// there might be a _reply-to in which case, we would ignore the _from
if($emailArr['replyto']){
	$to = $emailArr['replyto'];
} else {
	$to = $emailArr['from'];
}
$to = addressListHTML($to);

$cc = null;
if ($_GET['reply']=='all') { // the user choose reply-to-all
	// the $cc will be a combination of the origional message _cc and _to
	$ccArr = array_merge($emailArr['to'], $emailArr['cc']);
	$ccArr = removeFromArray($ccArr, $from[0]['email'], 'email'); // remove self from the reply to group
	if ($ccArr) {
		$cc = addressListHTML($ccArr);
		$cc = "<p><span class = 'vars'>CC:</span><span>" . $cc . "</span></p>";
	}
}

$oldFrom = addressListHTML($emailArr['from'], ', ');

$oldMessageArr = $emailArr['message'];
if (isset($oldMessageArr['html'])) {
	$oldMessage = convert_html_to_text(htmlspecialchars_decode($oldMessageArr['html']));
} else {
	$oldMessage = $oldMessageArr['text'];
}
$oldMessage = htmlspecialchars($oldMessage);
$oldMessage = str_replace("\r", '', $oldMessage);
$oldMessage = str_replace("\n", '<br />&gt; ', $oldMessage);
$oldMessage = '&gt; ' . $oldMessage;
$oldMessage = 'On ' . '<span id="then">' . date(RFC2822, strtotime($emailArr['datetime'])) . '</span>' . ' ' . $oldFrom . ' wrote:' . '<br /><br />' . $oldMessage;
$oldMessage = '<span id="oldMsg" style="color:#666666;line-height:1;">' . $oldMessage . '</span>';
$oldMessage = htmlspecialchars($oldMessage);

if (strpos($_SERVER['HTTP_USER_AGENT'], 'Mobi') !== false) {
	$platform = 'mobile';
} else {
	$platform = 'desktop';
}


$message = '<br /><br />';
$message .= $oldMessage;


$smarty->assign('QUERY_STRING', $_SERVER['QUERY_STRING']);
$smarty->assign('subject', $subject);
$smarty->assign('from', addressListHTML($from));
$smarty->assign('date', date(RFC2822));
$smarty->assign('to', $to);
$smarty->assign('cc', $cc);
$smarty->assign('platform', $platform);
$smarty->assign('message', $message);
$smarty->display('reply.tpl');
?>