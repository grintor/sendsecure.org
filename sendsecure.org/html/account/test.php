<?php
require('session.php'); // required on all pages in which you should be logged in to visit
require_once('../../resources/sqliConnect.php');
require_once('../../resources/functions.php');


print_r(deleteSubscription($_SESSION['sessionuser']));

?>