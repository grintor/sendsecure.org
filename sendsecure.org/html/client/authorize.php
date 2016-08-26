<?php
require_once('../../resources/smarty-3.1.30/Smarty.class.php');
require_once('../../resources/functions.php');
$smarty = new Smarty;
$smarty->setCompileDir('/tmp/smarty-templates_c');
$smarty->setCacheDir('/tmp/smarty-cache');
$smarty->setTemplateDir('../../resources/smarty-template_dir');

$apiURL = 'https://www.sendsecure.org/APIv1?id=' . $_GET['id'] . '&key=' . $_GET['key'];

$context = stream_context_create(array(
    'http' => array('ignore_errors' => true),
));
$emailArr = json_decode(file_get_contents($apiURL, false, $context), true);
if ($emailArr['response']['error']) {
	header("Location: error.php?error=" . $emailArr['response']['code']);
	die;
}

// get the email address from _rcpttos based on the index
$address[0]['email'] = $emailArr['rcpttos'][$_GET['index']];
$address[0]['name']  = null;
// get the name (if availble) corrasponding to that email address from _to
foreach($emailArr['to'] as $t) {
	if ($t['email']==$address[0]['email']){
		$address[0]['name']=$t['name'];
	}
}
// also try looking for the name in _cc
foreach($emailArr['cc'] as $t) {
	if ($t['email']==$address[0]['email']){
		$address[0]['name']=$t['name'];
	}
}

$smarty->assign('address', addressListHTML($address));
$smarty->assign('QUERY_STRING', $_SERVER['QUERY_STRING']);
$smarty->display('authorize.tpl');