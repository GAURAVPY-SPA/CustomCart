// Local stand-ins for outgoing mail, so tests never send real email:
//   smtpSink(port)      a minimal SMTP server that records every message (PHP mail() and the SMTP transport)
//   sendgridStub(port)  an HTTP server answering like SendGrid's v3 Mail Send (records the requests)
const net = require('net');
const http = require('http');

function smtpSink(port) {
  const messages = [];
  const server = net.createServer((socket) => {
    let buffer = '';
    let data = null;
    let current = { from: '', to: [], data: '' };
    socket.write('220 pw-test-sink ESMTP\r\n');
    socket.on('data', (chunk) => {
      buffer += chunk.toString('latin1');
      let i;
      while (true) {
        if (data !== null) {
          const end = buffer.indexOf('\r\n.\r\n');
          if (end === -1) return;
          current.data = data + buffer.slice(0, end);
          buffer = buffer.slice(end + 5);
          data = null;
          messages.push(current);
          current = { from: '', to: [], data: '' };
          socket.write('250 OK queued\r\n');
          continue;
        }
        if ((i = buffer.indexOf('\r\n')) === -1) return;
        const line = buffer.slice(0, i);
        buffer = buffer.slice(i + 2);
        const cmd = line.slice(0, 4).toUpperCase();
        if (cmd === 'EHLO') socket.write('250-pw-test-sink\r\n250 8BITMIME\r\n');
        else if (cmd === 'HELO') socket.write('250 pw-test-sink\r\n');
        else if (cmd === 'MAIL') { current.from = line; socket.write('250 OK\r\n'); }
        else if (cmd === 'RCPT') { current.to.push(line); socket.write('250 OK\r\n'); }
        else if (cmd === 'DATA') { data = ''; socket.write('354 End data with <CR><LF>.<CR><LF>\r\n'); }
        else if (cmd === 'QUIT') { socket.end('221 Bye\r\n'); return; }
        else socket.write('250 OK\r\n'); // RSET, NOOP ...
      }
    });
    socket.on('error', () => {});
  });
  return new Promise((resolve, reject) => {
    server.once('error', reject);
    server.listen(port, '127.0.0.1', () => resolve({ messages, close: () => new Promise((r) => server.close(r)) }));
  });
}

function sendgridStub(port) {
  const requests = [];
  const state = { status: 202 };
  const server = http.createServer((req, res) => {
    let body = '';
    req.on('data', (c) => { body += c; });
    req.on('end', () => {
      requests.push({ method: req.method, url: req.url, headers: req.headers, body: body ? JSON.parse(body) : null });
      res.writeHead(state.status, { 'Content-Type': 'application/json' });
      res.end(state.status === 202 ? '' : JSON.stringify({ errors: [{ message: state.status === 401 ? 'The provided authorization grant is invalid, expired, or revoked' : 'The from address does not match a verified Sender Identity.', field: null }] }));
    });
  });
  return new Promise((resolve, reject) => {
    server.once('error', reject);
    server.listen(port, '127.0.0.1', () => resolve({ requests, state, close: () => new Promise((r) => server.close(r)) }));
  });
}

module.exports = { smtpSink, sendgridStub };
