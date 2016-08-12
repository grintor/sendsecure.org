<?php
if ($_POST[username] == "test" && $_POST[password] == "pass") {
	http_response_code(202);
} else {
	sleep(1);
	http_response_code(401);
}
?>