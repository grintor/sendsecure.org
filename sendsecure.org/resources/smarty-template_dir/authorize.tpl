<html>
	<head>
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
		<style>
			.container {
				width: 80%;
				margin: auto;
			}
			.notice {
				font-family: 'Lucida Sans Unicode','Lucida Grande', Verdana, Arial, Sans-Serif;
				font-size: 13px;
				background-color: rgb(244,244,244);
				border: 1px solid lightgrey;
				padding: 10px;
				padding-top: 0px;
			}
			input[type='submit'] {
				float: right;
				padding-top: 5px;
				padding-bottom: 5px;
				margin-top: 10px;
			}
		</style>
	</head>
	<body>
		<div class='container'>
			<div class='notice'>
				<h2>NOTICE:</h2>
				<p>This transmission contains confidential information belonging to the sender that is legally privileged and proprietary and may be subject to protection under the law, including the Health Insurance Portability and Accountability Act (HIPAA), the US Digital Millennium Copyright Act, as well as other US and international laws</p>
				Only the intended recipient: <font color='red'>{$address}</font>, is authorized to use it.
				If you are not the intended recipient, leave this page now and please notify the sender of the error.
				If you are the the intended recipient, you may click "I AGREE: Read Message" to proceed.
			</div>
			<form action="authorized.php?{$QUERY_STRING}" method="post">
				<input type='hidden' name='agree' value='true'>
				<input type='submit' value='I AGREE: Read Message'>
			</form>
		</div>
	</body>
</html>