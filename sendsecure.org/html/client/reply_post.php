<?php
require_once('../../resources/html2text-0.3.4/html2text.php');
require_once('../../resources/functions.php');
require_once('../../resources/smarty-3.1.30/Smarty.class.php');
require_once('../../resources/SECRET.php');
$smarty = new Smarty;
$smarty->setCompileDir('/tmp/smarty-templates_c');
$smarty->setCacheDir('/tmp/smarty-cache');
$smarty->setTemplateDir('../../resources/smarty-template_dir');

define("RFC2822", "D, d M Y H:i:s O");

session_start();
if (!isset($_SESSION[$_GET['id']]) || $_SESSION[$_GET['id']] != 'authorized') {
	header('Location: authorize.php?' . $_SERVER['QUERY_STRING']);
}

$emailArr = apiGetMessage($_GET['id'], $_GET['key']);

// get the email address from _rcpttos based on the index
$message_data['from'][0] = indexToAddress($_GET['index'], $emailArr);

// there might be a _reply-to in which case, we would ignore the _from
if($emailArr['replyto']){
	$message_data['to'] = $emailArr['replyto'];
} else {
	$message_data['to'] = $emailArr['from'];
}

if ($_GET['reply']=='all') { // the user choose reply-to-all
	// the $cc will be a combination of the origional message _cc and _to
	$message_data['cc'] = array_merge($emailArr['to'], $emailArr['cc']);
	$message_data['cc'] = removeFromArray($message_data['cc'], $message_data['from'][0]['email'], 'email'); // remove self from the reply to group
} else {
	$message_data['cc'] = [];
}

$index = 0;
$message_data['attachments'] = [];
foreach($_FILES as $f){
	$message_data['attachments'][$index]['filename']  = $f['name'];
	$message_data['attachments'][$index]['content']  = base64_encode(file_get_contents($f['tmp_name']));
	unlink($f['tmp_name']);
	$index++;
}

// combine _cc and _to to make _rcpttos
foreach($message_data['cc'] as $cc){
	$message_data['rcpttos'][] = $cc['email'];
}
foreach($message_data['to'] as $to){
	$message_data['rcpttos'][] = $to['email'];
}

$message_data['subject'] = 'RE: ' . $emailArr['subject'];


$message_data['smtpuser'] = SELF_USER;
$message_data['mailfrom'] = [];
$message_data['peer'] = [];
$message_data['encoding'] = null;
$message_data['datetime'] = date('Y-m-d H:i:s');
$message_data['headers'] = null;
$message_data['reply-to'] = [];

$message_data['message']['text'] = convert_html_to_text($_POST['message']);
$message_data['message']['html'] = $_POST['message'];

$data_string = json_encode($message_data); 
$ch = curl_init('https://www.sendsecure.org/APIv1/');
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
	'Content-Type: application/json',
	'Content-Length: ' . strlen($data_string))
);

$result = curl_exec($ch);
// TODO: error handle

//--------------------------------------------------


if (strpos($_SERVER['HTTP_USER_AGENT'], 'Mobi') !== false) {
	$platform = 'mobile';
} else {
	$platform = 'desktop';
}

$cc = null;
if ($message_data['cc']) {
	$cc = addressListHTML($message_data['cc']);
	$cc = "<p><span class = 'vars'>CC:</span><span>" . $cc . "</span></p>";
}

$smarty->assign('subject', $message_data['subject']);
$smarty->assign('from', addressListHTML($message_data['from']));
$smarty->assign('date', date(RFC2822));
$smarty->assign('to', addressListHTML($message_data['to']));
$smarty->assign('platform', $platform);
$smarty->assign('cc', $cc);
$smarty->assign('message', htmlspecialchars_decode($message_data['message']['html']));
$smarty->display('reply_post.tpl');


?>