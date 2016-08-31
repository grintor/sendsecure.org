import sys
sys.dont_write_bytecode = True
from lib_auth_smtpd import SMTPServer
import asyncore

from subprocess import Popen, PIPE, STDOUT
from requests import post
from http.client import HTTPSConnection
from cgi import escape as htmlspecialchars
import json

class authSMTP():

	def __init__(self):
		self.username = None
		self.password = None

	class credentialManager(object):
		def validate(self, username, password):
			r = post("https://www.sendsecure.org/APIv1/auth/", data = {'username':username, 'password':password})
			print('AUTH! API response:', r.text)
			if r.status_code == 202:
				authSMTP.username = username
				authSMTP.password = password
				return True
			return False

	class server(SMTPServer):
		def process_message(self, peer, mailfrom, rcpttos, message_data):
			p = Popen(['python', 'mailtojson.py', '-p'], stdout=PIPE, stdin=PIPE, stderr=STDOUT)
			mailtojson_stdout = p.communicate(input=bytes(message_data, 'UTF-8'))[0]
			mailtojson_stdout = json.loads(mailtojson_stdout.decode())
			mailtojson_stdout['rcpttos'] = rcpttos;
			mailtojson_stdout['mailfrom'] = mailfrom;
			mailtojson_stdout['peer'] = peer;
			mailtojson_stdout['smtpuser'] = authSMTP.username;
			mailtojson_stdout['smtppass'] = authSMTP.password;

			conn = HTTPSConnection("www.sendsecure.org")
			headers = { "charset" : "utf-8", "Content-Type": "application/json", "User-Agent": "SendSecure/MailEncode 1.0" }
			postJson = json.dumps(mailtojson_stdout, ensure_ascii = False)
			conn.request("POST", "/APIv1/incoming/2525/", postJson.encode('utf-8'), headers)
			response = conn.getresponse()
			print('Posted! API response:', response.read())
			conn.close()
			#print(postJson)


authSmtpServer = authSMTP.server(
	localaddr = ('127.0.0.1', 2525),
	remoteaddr = None,
	credential_validator = authSMTP.credentialManager(),
	data_size_limit = 35650000
	)

print ('Server is started')
asyncore.loop()
