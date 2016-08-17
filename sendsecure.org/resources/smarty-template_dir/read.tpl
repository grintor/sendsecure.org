<html>
	<head>
		<link rel="stylesheet" type="text/css" href="{$css}">
		<script src='moment.min.js'></script>
		<script src='read.js'></script>
	</head>
	<body>
		<div class = 'mailHead'>
			<p><span class = 'vars'>Subject:</span><span><b>{$subject}</b></span></p>
			<p><span class = 'vars'>From:</span><span>{$from}</span></p>
			<p><span class = 'vars'>Date:</span><span id='now'>{$date}</span></p>
			<p><span class = 'vars'>To:</span><span>{$to}</span></p>
			{$replyTo}
			{$cc}
			{$attachments}
			<p class='options'><span class = 'vars'>Options:</span><span>
				<a href="#" onclick="window.print();">Print this page</a> | 
				<a href="#" onclick="window.print();">View Message Details</a> | 
				<a href="#" onclick="window.print();">Forward</a> | 
				<a href="reply.php?id={$id}&key={$key}&index={$index}&reply=one" >Reply</a> | 
				<a href="reply.php?id={$id}&key={$key}&index={$index}&reply=all" >Reply To All</a>
			</span></p>
		</div>
		<div class = 'mailBody'>
			<span class = "mailMessage">{$message}</span>
		<div>
		</div>
	</body>
</html>