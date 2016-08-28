import smtpd
import asyncore

class CustomSMTPServer(smtpd.SMTPServer):

    def process_message(self, peer, mailfrom, rcpttos, data):
        print ('Receiving message from:', peer)
        print ('Message addressed from:', mailfrom)
        print ('Message addressed to  :', rcpttos)
        print ('(Message length        :', len(data))
        return

server = CustomSMTPServer(
	localaddr = ('172.31.61.147', 25),
	remoteaddr = None,
	data_size_limit = 35650000
	)
print ('Server is gestart')

asyncore.loop()