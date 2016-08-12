<html>
<head>
	<link href="kindeditor-4.1.11-en/themes/default/default.css" rel="stylesheet" />
	<link rel="stylesheet" type="text/css" href="reply.css" />
	<script src='kindeditor-4.1.11-en/kindeditor-all-min.js'></script>
	<script src='moment.min.js'></script>
	<script src='reply.js'></script>
</head>
<body>
	<div class = 'mailHead'>
		<p><span class = 'vars'>Subject:</span><span><b>{$subject}</b></span></p>
		<p><span class = 'vars'>From:</span><span>{$from}</span></p>
		<p><span class = 'vars'>Date:</span><span id='now'>{$date}</span></p>
		<p><span class = 'vars'>To:</span><span>{$to}</span></p>
		{$cc}
		<form action='reply_post.php' method='post' enctype='multipart/form-data'>
			<p id='attachments'><span class = 'vars'>Attachments:</span><span id='attach-names'></span></p>
			<span id='attach-container'></span>
			<input type="hidden" name="id" value='{$id}' />
			<input type="hidden" name="key" value='{$key}' />
			<input type="hidden" name="index" value='{$index}' />
			<input type="hidden" name="reply" value='{$reply}' />
		</form>
		<p class='options'><span class = 'vars'>Options:</span><span>
			<a href="#" onclick='print1()'>Print This Page</a>
			<span id='add-attach'> | <a href="#" onclick="addAttach()">Add Attachment</a></span>
			<span id='remove-attach'> | <a href="#" onclick="removeAttach()">Remove Attachments</a></span>
			<span> | <a href="#" onclick="send();">Send</a></span>
		</span></p>
	</div>
	<div class = 'mailBody'>
		<span class = "mailMessage"></span>
		<input type="hidden" id="message" value='{$message}'/>
		<textarea id="kind-editor"></textarea>
	</div>
</body>
</html>