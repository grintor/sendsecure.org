from smtpd import SMTPServer
import asyncore
from http.client import HTTPSConnection
import json
from subprocess import Popen, PIPE, STDOUT

class CustomSMTPServer(SMTPServer):

	def process_message(self, peer, mailfrom, rcpttos, message_data):
		print('incoming mail')
		p = Popen(['python', 'mailtojson.py', '-p'], stdout=PIPE, stdin=PIPE, stderr=STDOUT)
		mailtojson_stdout = p.communicate(input=bytes(message_data, 'UTF-8'))[0]
		mailtojson_stdout = json.loads(mailtojson_stdout.decode())
		mailtojson_stdout['rcpttos'] = rcpttos;
		mailtojson_stdout['mailfrom'] = mailfrom;
		mailtojson_stdout['peer'] = peer;

		
		
		conn = HTTPSConnection("www.sendsecure.org")
		headers = { "charset" : "utf-8", "Content-Type": "application/json", "User-Agent": "SendSecure/MailEncode 1.0" }
		postJson = json.dumps(mailtojson_stdout, ensure_ascii = False)
		conn.request("POST", "/APIv1/incoming/25/", postJson.encode('utf-8'), headers)
		response = conn.getresponse()
		print('Posted! API response:', response.read())
		conn.close()
		#print(postJson)


server = CustomSMTPServer(
	localaddr = ('172.31.61.147', 25),
	remoteaddr = None,
	data_size_limit = 1000000,
)
print ('Server is started')

asyncore.loop()