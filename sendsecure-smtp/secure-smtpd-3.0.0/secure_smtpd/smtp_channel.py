import secure_smtpd
import smtpd, base64, secure_smtpd, asynchat, logging

from asyncore import ExitNow
from smtpd import NEWLINE, EMPTYSTRING

def decode_b64(data):
    '''Wrapper for b64decode, without having to struggle with bytestrings.'''
    byte_string = data.encode('utf-8')
    decoded = base64.b64decode(byte_string)
    return decoded.decode('utf-8')

def encode_b64(data):
    '''Wrapper for b64encode, without having to struggle with bytestrings.'''
    byte_string = data.encode('utf-8')
    encoded = base64.b64encode(byte_string)
    return encoded.decode('utf-8')

class SMTPChannel(smtpd.SMTPChannel):
    
    def __init__(self, smtp_server, newsocket, fromaddr, require_authentication=False, credential_validator=None, map=None, data_size_limit=33554432):
        smtpd.SMTPChannel.__init__(self, smtp_server, newsocket, fromaddr)
        asynchat.async_chat.__init__(self, newsocket, map=map)
        
        self.require_authentication = require_authentication
        self.data_size_limit = data_size_limit
        self.authenticating = False
        self.authenticated = False
        self.username = None
        self.password = None
        self.credential_validator = credential_validator
        self.logger = logging.getLogger( secure_smtpd.LOG_NAME )
    
    def smtp_QUIT(self, arg):
        self.push('221 Bye')
        self.close_when_done()
        raise ExitNow()
        
    def collect_incoming_data(self, data):
        limit = None
        if self.smtp_state == self.COMMAND:
            limit = self.max_command_size_limit
        elif self.smtp_state == self.DATA:
            limit = self.data_size_limit
        if limit and self.num_bytes > limit:
            return
        elif limit:
            self.num_bytes += len(data)
        self.received_lines.append(str(data, "utf-8"))
            
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
        if 'PLAIN' in arg:
            split_args = arg.split(' ')
            # second arg is Base64-encoded string of blah\0username\0password
            authbits = decode_b64(split_args[1]).split('\0')
            self.username = authbits[1]
            self.password = authbits[2]
            if self.credential_validator and self.credential_validator.validate(self.username, self.password):
                self.authenticated = True
                self.push('235 Authentication successful.')
            else:
                self.push('454 Temporary authentication failure.')
                raise ExitNow()
 
        elif 'LOGIN' in arg:
            self.authenticating = True
            split_args = arg.split(' ')
            
            # Some implmentations of 'LOGIN' seem to provide the username
            # along with the 'LOGIN' stanza, hence both situations are
            # handled.
            if len(split_args) == 2:
                self.username = decode_b64(arg.split(' ')[1])
                self.push('334 ' + encode_b64('Username'))
            else:
                self.push('334 ' + encode_b64('Username'))
                
        elif not self.username:
            self.username = decode_b64(arg)
            self.push('334 ' + encode_b64('Password'))
        else:
            self.authenticating = False
            self.password = decode_b64(arg)
            if self.credential_validator and self.credential_validator.validate(self.username, self.password):
                self.authenticated = True
                self.push('235 Authentication successful.')
            else:
                self.push('454 Temporary authentication failure.')
                raise ExitNow()
    
    # This code is taken directly from the underlying smtpd.SMTPChannel
    # support for AUTH is added.

    def found_terminator(self):
        line = EMPTYSTRING.join(self.received_lines)
        self.received_lines = []
        if self.smtp_state == self.COMMAND:
            sz, self.num_bytes = self.num_bytes, 0
            if not line:
                self.push('500 Error: bad syntax')
                return
            method = None
            i = line.find(' ')
            if self.authenticating:
                # If we are in an authenticating state, call the
                # method smtp_AUTH.
                arg = line.strip()
                command = 'AUTH'
            elif i < 0:
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

            # White list of operations that are allowed prior to AUTH.
            if not command in ['AUTH', 'EHLO', 'HELO', 'NOOP', 'RSET', 'QUIT']:
                if self.require_authentication and not self.authenticated:
                    self.push('530 Authentication required')
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
            for text in line.split('\r\n'):
                if text and text[0] == '.':
                    data.append(text[1:])
                else:
                    data.append(text)
            self.received_data = NEWLINE.join(data)
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