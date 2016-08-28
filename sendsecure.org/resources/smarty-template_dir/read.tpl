<html>
	<head>
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
		<link rel="stylesheet" type="text/css" href="read.{$platform}.css">
		<script src='moment.min.js'></script>
		<script src='read.js'></script>
	</head>
	<body>
		<div class = 'mailHead'>
			<p><span class = 'vars'>Subject:</span><span><b>{$subject}</b></span></p>
			<p><span class = 'vars'>From:</span><span>{$from}</span></p>
			<p><span class = 'vars'>Date:</span><span id='now'>{$date}</span></p>
			<p><span class = 'vars'>To:</span><span>{$to}</span></p>
			{strip}
			{$replyTo}
			{$cc}
			{$attachments}
			<p class='options'><span class = 'vars'>Options:</span>
				<span><a href="#" onclick="window.print();">Print this page</a></span>
				<span><a href="#" onclick="window.print();">View Message Details</a></span>
				<span><a href="#" onclick="window.print();">Forward</a></span>
				<span><a href="reply.php?{$QUERY_STRING}&reply=one" >Reply</a></span>
				<span><a href="reply.php?{$QUERY_STRING}&reply=all" >Reply To All</a></span>
			</p>
			{/strip}
		</div>
		<div class = 'mailBody'>
			<span class = "mailMessage">{$message}</span>
		<div>
		</div>
	</body>
</html>