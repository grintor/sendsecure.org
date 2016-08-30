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
			if r.status_code == 202:
				authSMTP.username = username
				authSMTP.password = password
				return True
			return False

	class server(SMTPServer):
		def process_message(self, peer, mailfrom, rcpttos, message_data):
			p = Popen(['python', 'mailtojson.py', '-p'], stdout=PIPE, stdin=PIPE, stderr=STDOUT)
			mailtojson_stdout = p.communicate(input=bytes(message_data, 'UTF-8'))[0]
			#print(mailtojson_stdout.decode())
			mailtojson_stdout = json.loads(mailtojson_stdout.decode())
			mailtojson_stdout['rcpttos'] = rcpttos;
			mailtojson_stdout['smtpuser'] = authSMTP.username;
			mailtojson_stdout['smtppass'] = authSMTP.password;

			# here we normalize the data for the api
			mailtojson_stdout['message1'] = {};
			for e in mailtojson_stdout['message']:
				if e['content_type'] == 'text/plain':
					mailtojson_stdout['message1']['text'] = e['content']
				if e['content_type'] == 'text/html':
					mailtojson_stdout['message1']['html'] = htmlspecialchars(e['content'])
			del mailtojson_stdout['message']
			mailtojson_stdout['message'] = mailtojson_stdout.pop('message1')
			# rather than leave subject blank, fill with (No Subject)
			if (mailtojson_stdout['subject'] == ''):
				mailtojson_stdout['subject'] = '(No Subject)'
			# this happens if all recipients are BCC
			if (mailtojson_stdout['to'][0]['name'] == 'undisclosed-recipients:'):
				del mailtojson_stdout['to'][0]

			#print (json.dumps(mailtojson_stdout, ensure_ascii = False))

			conn = HTTPSConnection("www.sendsecure.org")
			headers = { "charset" : "utf-8", "Content-Type": "application/json", "User-Agent": "SendSecure/MailEncode 1.0" }
			postJson = json.dumps(mailtojson_stdout, ensure_ascii = False)
			conn.request("POST", "/APIv1/", postJson.encode('utf-8'), headers)
			response = conn.getresponse()
			print('Sent! API response:', response.read())
			conn.close()


authSmtpServer = authSMTP.server(
	localaddr = ('127.0.0.1', 2525),
	remoteaddr = None,
	credential_validator = authSMTP.credentialManager(),
	data_size_limit = 35650000
	)

print ('Server is started')
asyncore.loop()
