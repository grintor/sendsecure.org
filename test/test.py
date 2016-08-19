
from smtpd2 import SMTPServer
		

class credentialManager(object):
	def validate(self, username, password):
		r = post("https://www.sendsecure.org/APIv1/auth/", data = {'username':username, 'password':password})
		if r.status_code == 202:
			return True
		return False

class CustomSMTPServer(SMTPServer):
	
	def process_message(self, peer, mailfrom, rcpttos, data):
		print('Receiving message from:', peer)
		print('Message addressed from:', mailfrom)
		print('Message addressed to	 :', rcpttos)
		print('Message length		 :', len(data))
		return

authSmtpServer = CustomSMTPServer(
		localaddr = ('0.0.0.0', 2525),
		remoteaddr = None,
		credential_validator=credentialManager(),
		data_size_limit=35650000
	)

authSmtpServer.run()
