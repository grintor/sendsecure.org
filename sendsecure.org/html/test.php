<?php
$headers =  'From: cwheeler@georgiatc.com';
$subject = 'asdasd';
$message = 'dddddd';
$rcptto = 'asdfsagyh54h46w4saf@gmail.com';
$uniqid = 124356;
$additional = "-rbounce-$uniqid@sendsecure.org -ODeliveryMode=background";
mail($rcptto, $subject, $message, $headers, $additional);
?>