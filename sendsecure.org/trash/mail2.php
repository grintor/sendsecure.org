<?php

$headers = 'From: webmaster@sendsecure.org' . "\r\n" .
    'Reply-To: webmaster@sendsecure.org' . "\r\n" .
    'X-Mailer: PHP/' . phpversion();
	
mail('cwheeler@georgiatc.com', 'My Subject', 'test', $headers);
?>