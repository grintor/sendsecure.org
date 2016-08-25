<html>
	<head>
	</head>
	<body>
		<div>
			Confidentiality Notice: All information in this message and attachments are confidential and may be legally
			privileged. Only the intended recipient: {$address} is authorized to use it. If you are not the intended
			recipient please leave this page now and notify the sender of the error. If you are the the intended recipient:
			{$address} you may click "Read Message" to proceed.
		</div>
		<form action="authorized.php?{$QUERY_STRING}" method="post">
			<input type = 'hidden' name='agree' value='true'>
			<input type = 'submit' value='Read Message'>
		</form>
	</body>
</html>