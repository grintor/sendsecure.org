import sys
sys.dont_write_bytecode = True

import smtpd
import asyncore
import asynchat
import collections
import socket
import time
import base64
from asyncore import ExitNow
import signal
from multiprocessing import Process
import traceback
from warnings import warn

__version__ = 'Thanks for us choosing to SendSecure!'

#edited
class Devnull:
	def write(self, msg):
		#return
		sys.stderr.write(msg)
	def flush(self):
		#return
		sys.stderr.flush(self)

DEBUGSTREAM = Devnull()

class SMTPChannel(smtpd.SMTPChannel): #edited
	COMMAND = 0
	DATA = 1

	command_size_limit = 512
	command_size_limits = collections.defaultdict(lambda x=command_size_limit: x)

	def __init__(self, server, conn, addr, credential_validator, data_size_limit=smtpd.DATA_SIZE_DEFAULT, map=None, enable_SMTPUTF8=False, decode_data=None): #edited
		asynchat.async_chat.__init__(self, conn, map=map)
		self.smtp_server = server
		self.conn = conn
		self.addr = addr
		#added
		self.credential_validator = credential_validator
		self.authenticating = False
		self.authenticated = False
		self.username = None
		self.password = None
		# /added
		self.data_size_limit = data_size_limit
		self.enable_SMTPUTF8 = enable_SMTPUTF8
		if enable_SMTPUTF8:
			if decode_data:
				raise ValueError("decode_data and enable_SMTPUTF8 cannot"
								 " be set to True at the same time")
			decode_data = False
		if decode_data is None:
			warn("The decode_data default of True will change to False in 3.6;"
				 " specify an explicit value for this keyword",
				 DeprecationWarning, 2)
			decode_data = True
		self._decode_data = decode_data
		if decode_data:
			self._emptystring = ''
			self._linesep = '\r\n'
			self._dotsep = '.'
			self._newline = smtpd.NEWLINE #edited
		else:
			self._emptystring = b''
			self._linesep = b'\r\n'
			self._dotsep = ord(b'.')
			self._newline = b'\n'
		self._set_rset_state()
		self.seen_greeting = ''
		self.extended_smtp = False
		self.command_size_limits.clear()
		self.fqdn = socket.getfqdn()
		try:
			self.peer = conn.getpeername()
		except OSError as err:
			# a race condition	may occur if the other end is closing
			# before we can get the peername
			self.close()
			if err.args[0] != errno.ENOTCONN:
				raise
			raise ExitNow() #added
			return
		print('Peer:', repr(self.peer), file=DEBUGSTREAM)
		self.push('220 %s %s' % (self.fqdn, __version__))


	# Implementation of base class abstract method
	def found_terminator(self):
		line = self._emptystring.join(self.received_lines)
		#print('Data:', repr(line), file=DEBUGSTREAM)
		self.received_lines = []
		if self.smtp_state == self.COMMAND:
			sz, self.num_bytes = self.num_bytes, 0
			if not line:
				self.push('500 Error: bad syntax')
				return
			if not self._decode_data:
				line = str(line, 'utf-8')
			i = line.find(' ')
			# added
			if self.authenticating:
				# If we are in an authenticating state, call the
				# method smtp_AUTH.
				arg = line.strip()
				command = 'AUTH'
			# /added
			elif i < 0: #edited
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
			for text in line.split(self._linesep):
				if text and text[0] == self._dotsep:
					data.append(text[1:])
				else:
					data.append(text)
			self.received_data = self._newline.join(data)
			args = (self.peer, self.mailfrom, self.rcpttos, self.received_data)
			kwargs = {}
			if not self._decode_data:
				kwargs = {
					'mail_options': self.mail_options,
					'rcpt_options': self.rcpt_options,
				}
			status = self.smtp_server.process_message(*args, **kwargs)
			self._set_post_data_state()
			if not status:
				self.push('250 OK')
			else:
				self.push(status)

	#edited
	def smtp_HELO(self, arg):
		self.push('501 Syntax: EHLO hostname')
		return

	def smtp_EHLO(self, arg):
		if not arg:
			self.push('501 Syntax: EHLO hostname')
			return
		# See issue #21783 for a discussion of this behavior.
		if self.seen_greeting:
			self.push('503 Duplicate HELO/EHLO')
			return
		self._set_rset_state()
		self.seen_greeting = arg
		self.extended_smtp = True
		self.push('250-%s' % self.fqdn)
		if self.data_size_limit:
			self.push('250-SIZE %s' % self.data_size_limit)
			self.command_size_limits['MAIL'] += 26
		if not self._decode_data:
			self.push('250-8BITMIME')
		if self.enable_SMTPUTF8:
			self.push('250-SMTPUTF8')
			self.command_size_limits['MAIL'] += 10
		self.push('250-AUTH LOGIN PLAIN') #added
		self.push('250 HELP')

	def smtp_QUIT(self, arg):
		# args is ignored
		self.push('221 Bye')
		self.close_when_done()
		raise ExitNow() # added

	#added
	def smtp_AUTH(self, arg):

		def decode_b64(data):
			try:
				return base64.b64decode(data.encode('utf-8')).decode('utf-8')
			except:
				return None

		def encode_b64(data):
			return base64.b64encode(data.encode('utf-8')).decode('utf-8')
			
		if not arg:
			self.push('500 Error: bad syntax - missing argument')
			return

		if 'PLAIN' in arg:
			split_args = arg.split(' ')
			if not len(split_args)==2:
				self.push('500 Error: bad syntax - invalid number of arguments after PLAIN')
				self.authenticating = False
				return
			# second arg is Base64-encoded string of blah\0username\0password... or bad syntax
			pre_authbits = decode_b64(split_args[1])
			if not pre_authbits:
				self.push('500 Error: bad syntax - invalid base64')
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
					self.push('500 Error: bad syntax - invalid base64')
					self.authenticating = False
					return
				self.push('334 ' + encode_b64('Username'))
			else:
				self.push('334 ' + encode_b64('Username'))
				
		elif not self.username:
			self.username = decode_b64(arg)
			if not self.username:
				self.push('500 Error: bad syntax - invalid base64')
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
	

class SMTPServer(smtpd.SMTPServer): #edited
	# SMTPChannel class to use for managing client connections
	channel_class = SMTPChannel

	def __init__(self, localaddr, remoteaddr, credential_validator, data_size_limit=smtpd.DATA_SIZE_DEFAULT, map=None, enable_SMTPUTF8=False, decode_data=None): #edited
		self._localaddr = localaddr
		self._remoteaddr = remoteaddr
		self.credential_validator = credential_validator #added
		self.data_size_limit = data_size_limit
		self.enable_SMTPUTF8 = enable_SMTPUTF8
		if enable_SMTPUTF8:
			if decode_data:
				raise ValueError("The decode_data and enable_SMTPUTF8"
								 " parameters cannot be set to True at the"
								 " same time.")
			decode_data = False
		if decode_data is None:
			warn("The decode_data default of True will change to False in 3.6;"
				 " specify an explicit value for this keyword",
				 DeprecationWarning, 2)
			decode_data = True
		self._decode_data = decode_data
		asyncore.dispatcher.__init__(self, map=map)
		try:
			gai_results = socket.getaddrinfo(*localaddr,
											 type=socket.SOCK_STREAM)
			self.create_socket(gai_results[0][0], gai_results[0][1])
			# try to re-use a server port if possible
			self.set_reuse_addr()
			self.bind(localaddr)
			self.listen(5)
		except:
			traceback.print_exc(file=DEBUGSTREAM) #added
			self.close()
			raise ExitNow() # added
			raise
		else:
			print('%s started at %s\n\tLocal addr: %s\n\tRemote addr:%s' % (
				self.__class__.__name__, time.ctime(time.time()),
				localaddr, remoteaddr), file=DEBUGSTREAM)

	#edited			
	def handle_accepted(self, conn, addr):
		print('Incoming connection from %s' % repr(addr), file=DEBUGSTREAM)
		#added
		process = Process(target=self._accept_subprocess, args=( conn, addr))
		process.daemon = True
		process.start()
		#/added


	#added
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
			print('_accept_subprocess(): smtp channel terminated asyncore.', file=DEBUGSTREAM)
		except Exception as e:
			self._shutdown_socket(newsocket)
			traceback.print_exc(file=DEBUGSTREAM)

			
	#added
	def _shutdown_socket(self, s):
		try:
			s.shutdown(socket.SHUT_RDWR)
			s.close()
		except Exception as e:
			traceback.print_exc(file=DEBUGSTREAM)
			

	#added
	def run(self):
		asyncore.loop()
		if hasattr(signal, 'SIGTERM'):
			def sig_handler(signal,frame):
				print("Got signal %s, shutting down." % signal, file=DEBUGSTREAM)
				sys.exit(0)
			signal.signal(signal.SIGTERM, sig_handler)
		while 1:
			time.sleep(1)
