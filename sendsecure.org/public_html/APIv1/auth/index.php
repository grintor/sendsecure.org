<?php
if ($_POST[username] == "test" && $_POST[password] == "password3") {
	http_response_code(202);
} else {
	sleep(1);
	http_response_code(401);
}
?>