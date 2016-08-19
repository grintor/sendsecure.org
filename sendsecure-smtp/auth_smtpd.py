import smtpd
import asyncore
import asynchat
import collections
import socket
import time
import base64
from email._header_value_parser import get_addr_spec, get_angle_addr
from asyncore import ExitNow
import signal
from multiprocessing import Process, Queue
import sys

__welcome__ = 'Thanks for us choosing to SendSecure!'


class SMTPChannel(smtpd.SMTPChannel):
	COMMAND = 0
	DATA = 1

	command_size_limit = 512
	command_size_limits = collections.defaultdict(lambda x=command_size_limit: x)
	command_size_limits.update({
		'MAIL': command_size_limit + 26,
		})
	max_command_size_limit = max(command_size_limits.values())

	def __init__(self, server, conn, addr, credential_validator, data_size_limit=smtpd.DATA_SIZE_DEFAULT, map=None):
		asynchat.async_chat.__init__(self, conn, map=map)
		self.smtp_server = server
		self.conn = conn
		self.addr = addr
		self.data_size_limit = data_size_limit
		self.received_lines = []
		self.smtp_state = self.COMMAND
		self.seen_greeting = ''
		self.mailfrom = None
		self.rcpttos = []
		self.received_data = ''
		self.fqdn = socket.getfqdn()
		self.num_bytes = 0
		#added
		self.credential_validator = credential_validator
		self.authenticating = False
		self.authenticated = False
		self.username = None
		self.password = None
		# /added
		try:
			self.peer = conn.getpeername()
		except OSError as err:
			# a race condition	may occur if the other end is closing
			# before we can get the peername
			print('_accept_subprocess(): uncaught exception: %s' % str(e))
			raise ExitNow()

			if err.args[0] != errno.ENOTCONN:
				raise
			return
		#print('Peer:', repr(self.peer))
		self.push('220 %s %s' % (self.fqdn, __welcome__))
		self.set_terminator(b'\r\n')
		self.extended_smtp = False

	# Implementation of base class abstract method
	def found_terminator(self):
		line = smtpd.EMPTYSTRING.join(self.received_lines)
		#print('Data:', repr(line))
		self.received_lines = []
		if self.smtp_state == self.COMMAND:
			sz, self.num_bytes = self.num_bytes, 0
			if not line:
				self.push('500 Error: bad syntax')
				return
			method = None
			i = line.find(' ')
			# added
			if self.authenticating:
				# If we are in an authenticating state, call the
				# method smtp_AUTH.
				arg = line.strip()
				command = 'AUTH'
			# /added
			elif i < 0: # changed from 'if'
				command = line.upper()
				arg = None
			else:
				command = line[:i].upper()
				arg = line[i+1:].strip()
			max_sz = (self.command_size_limits[command]
						if self.extended_smtp else self.command_size_limit)
			if sz > max_sz:
				self.push('500 Error: line too long')
				return
			method = getattr(self, 'smtp_' + command, None)
			if not method:
				self.push('500 Error: command "%s" not recognized' % command)
				return
			# added
			# White list of operations that are allowed prior to AUTH.
			if not command in ['AUTH', 'EHLO', 'HELO', 'NOOP', 'RSET', 'QUIT', 'HELP']:
				if not self.authenticated:
					self.push('530 Authentication required')
					return
			# /added
			method(arg)
			return
		else:
			if self.smtp_state != self.DATA:
				self.push('451 Internal confusion')
				self.num_bytes = 0
				return
			if self.data_size_limit and self.num_bytes > self.data_size_limit:
				self.push('552 Error: Too much mail data')
				self.num_bytes = 0
				return
			# Remove extraneous carriage returns and de-transparency according
			# to RFC 5321, Section 4.5.2.
			data = []
			for text in line.split('\r\n'):
				if text and text[0] == '.':
					data.append(text[1:])
				else:
					data.append(text)
			self.received_data = smtpd.NEWLINE.join(data)
			status = self.smtp_server.process_message(self.peer,
													  self.mailfrom,
													  self.rcpttos,
													  self.received_data)
			self.rcpttos = []
			self.mailfrom = None
			self.smtp_state = self.COMMAND
			self.num_bytes = 0
			self.set_terminator(b'\r\n')
			if not status:
				self.push('250 OK')
			else:
				self.push(status)

	# SMTP and ESMTP commands
	def smtp_HELO(self, arg):
		self.push('501 Syntax: EHLO hostname')

	def smtp_EHLO(self, arg):
		if not arg:
			self.push('501 Syntax: EHLO hostname')
			return
		if self.seen_greeting:
			self.push('503 Duplicate HELO/EHLO')
		else:
			self.seen_greeting = arg
			self.extended_smtp = True
			self.push('250-%s' % self.fqdn)
			if self.data_size_limit:
				self.push('250-SIZE %s' % self.data_size_limit)
				self.push('250-AUTH LOGIN PLAIN')
				self.push('250 HELP')
				
	def smtp_AUTH(self, arg):

		def decode_b64(data):
			try:
				return base64.b64decode(data.encode('utf-8')).decode('utf-8')
			except:
				return None

		def encode_b64(data):
			return base64.b64encode(data.encode('utf-8')).decode('utf-8')
			
		if not arg:
			self.push('500 Error: bad syntax')
			return

		if 'PLAIN' in arg:
			split_args = arg.split(' ')
			if not len(split_args)==2: # if there is nothing after PLAIN..or there is more than one thing..
				self.push('500 Error: bad syntax')
				self.authenticating = False
				return
			# second arg is Base64-encoded string of blah\0username\0password... or bad syntax
			pre_authbits = decode_b64(split_args[1])
			if not pre_authbits:
				self.push('500 Error: bad syntax')
				return
			authbits = pre_authbits.split('\0')
			self.username = authbits[1]
			self.password = authbits[2]
			if self.credential_validator and self.credential_validator.validate(self.username, self.password):
				self.authenticated = True
				self.push('235 Authentication successful.')
			else:
				self.push('454 Temporary authentication failure.')
				self.close_when_done()
				raise ExitNow()
 
		elif 'LOGIN' in arg:
			self.authenticating = True
			split_args = arg.split(' ')
			
			# Some implmentations of 'LOGIN' seem to provide the username
			# along with the 'LOGIN' stanza, hence both situations are
			# handled.
			if len(split_args) == 2:
				self.username = decode_b64(arg.split(' ')[1])
				if not self.username:
					self.push('500 Error: bad syntax')
					self.authenticating = False
					return
				self.push('334 ' + encode_b64('Username'))
			else:
				self.push('334 ' + encode_b64('Username'))
				
		elif not self.username:
			self.username = decode_b64(arg)
			if not self.username:
				self.push('500 Error: bad syntax')
				self.authenticating = False
				return
			self.push('334 ' + encode_b64('Password'))
		else:
			self.authenticating = False
			self.password = decode_b64(arg)
			if self.credential_validator and self.credential_validator.validate(self.username, self.password):
				self.authenticated = True
				self.push('235 Authentication successful.')
			else:
				self.push('454 Temporary authentication failure.')
				self.close_when_done()
				raise ExitNow()

	def smtp_QUIT(self, arg):
		# args is ignored
		self.push('221 Bye')
		self.close_when_done()
		raise ExitNow()

class SMTPServer(smtpd.SMTPServer):
	
	def __init__(self, localaddr, remoteaddr, credential_validator, data_size_limit=smtpd.DATA_SIZE_DEFAULT, map=None):
		self._localaddr = localaddr
		self._remoteaddr = remoteaddr
		self.data_size_limit = data_size_limit
		self.credential_validator = credential_validator
		asyncore.dispatcher.__init__(self, map=map)
		try:
			self.create_socket(socket.AF_INET, socket.SOCK_STREAM)
			# try to re-use a server port if possible
			self.set_reuse_addr()
			self.bind(localaddr)
			self.listen(5)
		except:
			#print('_accept_subprocess(): uncaught exception: %s' % str(e))
			self.shutdown(socket.SHUT_RDWR)
			self.close()
		else:

			print('%s started at %s\n\tLocal addr: %s\n\tRemote addr:%s' % (
				self.__class__.__name__, time.ctime(time.time()), localaddr, remoteaddr))
				
	def handle_accepted(self, conn, addr):
		#print('Incoming connection from %s' % repr(addr))		
		process = Process(target=self._accept_subprocess, args=( conn, addr))
		process.daemon = True
		process.start()
			
		
	def run(self):
		asyncore.loop()
		if hasattr(signal, 'SIGTERM'):
			def sig_handler(signal,frame):
				#print("Got signal %s, shutting down." % signal)
				sys.exit(0)
			signal.signal(signal.SIGTERM, sig_handler)
		while 1:
			time.sleep(1)
		
	def _accept_subprocess(self, newsocket, fromaddr):
		try:
			channel = SMTPChannel(
				server = self,
				conn = newsocket,
				addr = fromaddr,
				data_size_limit=self.data_size_limit,
				credential_validator=self.credential_validator,
			)
			asyncore.loop()
		except (ExitNow):
			self._shutdown_socket(newsocket)
			#print('_accept_subprocess(): smtp channel terminated asyncore.')
		except Exception as e:
			self._shutdown_socket(newsocket)
			#print('_accept_subprocess(): uncaught exception: %s' % str(e))
			
	def _shutdown_socket(self, s):
		try:
			s.shutdown(socket.SHUT_RDWR)
			s.close()
		except Exception as e:
			a=1+1 # placeholder so I can comment out the print
			#print('_shutdown_socket(): failed to cleanly shutdown socket: %s' % str(e))