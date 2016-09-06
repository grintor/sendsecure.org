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

$address[0] = indexToAddress($_GET['index'], $emailArr);

$smarty->assign('address', addressListHTML($address));
$smarty->assign('QUERY_STRING', $_SERVER['QUERY_STRING']);
$smarty->display('authorize.tpl');