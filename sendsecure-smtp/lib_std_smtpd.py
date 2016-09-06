import sys
sys.dont_write_bytecode = True
import smtpd
import sys
import asynchat

smtpd.DEBUGSTREAM = sys.stdout
smtpd.__version__ = 'Welcome to SendSecure!'

class SMTPChannel(smtpd.SMTPChannel): #edited
	def push(self, msg):
		asynchat.async_chat.push(self, bytes(
			msg + '\r\n', 'utf-8' if self.require_SMTPUTF8 else 'ascii'))
		print('TX:', msg, file=smtpd.DEBUGSTREAM) # added
		
		
	def smtp_RCPT(self, arg):
		if not self.seen_greeting:
			self.push('503 Error: send HELO first');
			return
		print('===> RCPT', arg, file=smtpd.DEBUGSTREAM) #edited
		if not self.mailfrom:
			self.push('503 Error: need MAIL command')
			return
		syntaxerr = '501 Syntax: RCPT TO: <address>'
		if self.extended_smtp:
			syntaxerr += ' [SP <mail-parameters>]'
		if arg is None:
			self.push(syntaxerr)
			return
		arg = self._strip_command_keyword('TO:', arg)
		address, params = self._getaddr(arg)
		if not address:
			self.push(syntaxerr)
			return
		if not self.extended_smtp and params:
			self.push(syntaxerr)
			return
		self.rcpt_options = params.upper().split()
		params = self._getparams(self.rcpt_options)
		if params is None:
			self.push(syntaxerr)
			return
		# XXX currently there are no options we recognize.
		if len(params.keys()) > 0:
			self.push('555 RCPT TO parameters not recognized or not implemented')
			return
		# added
		if address == 'do-not-reply-secure@sendsecure.org':
			error550msg	 = '550 You attempted to email a reply directly to: do-not-reply@sendsecure.org. '
			error550msg += 'This address does not accept emails. '
			error550msg += 'If you want to reply to a secure message, '
			error550msg += 'then click the URL and use the reply button on the webpage.'
			self.push(error550msg)
			return
		# /added
		self.rcpttos.append(address)
		print('recips:', self.rcpttos, file=smtpd.DEBUGSTREAM)	#edited
		self.push('250 OK')
		

	def found_terminator(self):
		line = self._emptystring.join(self.received_lines)
		# print('Data:', repr(line), file=smtpd.DEBUGSTREAM) #edited
		self.received_lines = []
		if self.smtp_state == self.COMMAND:
			print('RX:', repr(line), file=smtpd.DEBUGSTREAM) #added
			sz, self.num_bytes = self.num_bytes, 0
			if not line:
				self.push('500 Error: bad syntax')
				return
			if not self._decode_data:
				line = str(line, 'utf-8')
			i = line.find(' ')
			if i < 0:
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
		
class SMTPServer(smtpd.SMTPServer): #edited
	channel_class = SMTPChannel