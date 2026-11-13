// Outgoing mail (App\Libraries\Mailer, mail.method in .env): each transport sends through
// itself only, against local stand-ins (no real email leaves this machine):
//   PHP mail() and SMTP -> an SMTP sink on 127.0.0.1; Emailer API -> a SendGrid-like HTTP stub.
// Also: no method = PHP mail(), an unknown method / missing key is refused, a failing
// SMTP / API failure is reported and nothing reaches another transport, PHP mail() is not
// checked (always sent), and the Sitepanel enquiry Send Mail shows "Email sent successfully.".
const path = require('path');
const { execFile } = require('child_process');
const { test, expect } = require('@playwright/test');
const { sql, unique, watch, expectClean, PROJECT_ROOT } = require('../helpers');
const { smtpSink, sendgridStub } = require('../support/mail-stubs');

const ROOT = PROJECT_ROOT.replace(/\\/g, '/');
const SCRIPT = path.join(__dirname, '..', 'support', 'mail-send.php');
const ADMIN_URL = process.env.ADMIN_URL || 'http://localhost:8081/';
const TO = 'pw-test+mail@example.com';
const SINK_PORT = 2525;
const API_PORT = 8099;
const API_URL = `http://127.0.0.1:${API_PORT}/v3/mail/send`;

// Asynchronous on purpose: the stand-ins run in this process and must answer while PHP sends.
const send = (settings, mailIni = false) => new Promise((resolve, reject) => {
  const args = [SCRIPT, ROOT, JSON.stringify(settings), TO, ...(mailIni ? ['127.0.0.1', String(SINK_PORT)] : [])];
  execFile('php', args, { encoding: 'utf8', timeout: 60_000 }, (err, out) => {
    if (err) return reject(err);
    resolve(JSON.parse(out.trim().split(/\r?\n/).pop()));
  });
});

test.describe.serial('mail transports', () => {
  let sink;
  let api;

  test.beforeAll(async () => {
    sink = await smtpSink(SINK_PORT);
    api = await sendgridStub(API_PORT);
  });
  test.afterAll(async () => {
    await sink?.close();
    await api?.close();
  });
  test.beforeEach(() => {
    sink.messages.length = 0;
    api.requests.length = 0;
    api.state.status = 202;
  });

  test('method mail: PHP mail() only', async () => {
    const r = await send({ method: 'mail' }, true);
    expect(r).toEqual({ ok: true, error: '', method: 'mail' });
    expect(sink.messages).toHaveLength(1);
    expect(sink.messages[0].data).toContain('X-Mailer: CustomCart sitepanel'); // the native mail() headers
    expect(api.requests).toHaveLength(0);
  });

  test('no method set: PHP mail() is the default', async () => {
    const r = await send({ method: '' }, true);
    expect(r).toEqual({ ok: true, error: '', method: 'mail' });
    expect(sink.messages).toHaveLength(1);
    expect(api.requests).toHaveLength(0);
  });

  test('method smtp: the SMTP server only', async () => {
    // php.ini's mail() target is left at its default, so only an SMTP send could reach the sink.
    const r = await send({ method: 'smtp', smtp_host: '127.0.0.1', smtp_port: SINK_PORT, smtp_crypto: '' });
    expect(r).toEqual({ ok: true, error: '', method: 'smtp' });
    expect(sink.messages).toHaveLength(1);
    expect(sink.messages[0].to.join(' ')).toContain(TO);
    expect(sink.messages[0].data).not.toContain('X-Mailer: CustomCart sitepanel'); // not the mail() path
    expect(api.requests).toHaveLength(0);
  });

  test('method api: the SendGrid API only', async () => {
    const r = await send({ method: 'api', api_provider: 'sendgrid', api_key: 'SG.pw-test-key', api_url: API_URL }, true);
    expect(r).toEqual({ ok: true, error: '', method: 'api' });
    expect(api.requests).toHaveLength(1);
    const req = api.requests[0];
    expect(req.method).toBe('POST');
    expect(req.headers.authorization).toBe('Bearer SG.pw-test-key');
    expect(req.body.personalizations[0].to[0].email).toBe(TO);
    expect(req.body.from).toEqual({ email: 'pw-test-sender@example.com', name: 'PW Test' });
    expect(req.body.subject).toBe('PW-TEST transport api');
    expect(req.body.content[0]).toEqual({ type: 'text/html', value: '<p>PW-TEST message via api</p>' });
    expect(sink.messages, 'no mail() / SMTP send as well').toHaveLength(0);
  });

  test('a failing transport is reported, never sent another way', async () => {
    // API refuses (bad key): failure, the key is not in the message, nothing reaches the SMTP sink.
    api.state.status = 401;
    let r = await send({ method: 'api', api_key: 'SG.pw-test-key', api_url: API_URL }, true);
    expect(r.ok).toBe(false);
    expect(r.error).toContain('API key was refused');
    expect(r.error).not.toContain('SG.pw-test-key');
    expect(api.requests).toHaveLength(1);
    expect(sink.messages).toHaveLength(0);

    // API rejects the message (e.g. unverified sender): the provider's reason.
    api.state.status = 403;
    r = await send({ method: 'api', api_key: 'SG.pw-test-key', api_url: API_URL }, true);
    expect(r.ok).toBe(false);
    api.state.status = 400;
    r = await send({ method: 'api', api_key: 'SG.pw-test-key', api_url: API_URL }, true);
    expect(r.error).toContain('verified Sender Identity');
    expect(sink.messages).toHaveLength(0);

    // SMTP server unreachable: failure, and no mail() delivery although mail() would work.
    r = await send({ method: 'smtp', smtp_host: '127.0.0.1', smtp_port: 2599, smtp_crypto: '', smtp_timeout: 3 }, true);
    expect(r.ok).toBe(false);
    expect(r.error).toMatch(/could not connect/i);
    expect(sink.messages).toHaveLength(0);
    expect(api.requests).toHaveLength(3);

  });

  test('method mail: not checked, always counts as sent (a refusal is only logged)', async () => {
    const r = await send({ method: 'mail' }, false); // php.ini localhost:25, nothing listening
    expect(r).toEqual({ ok: true, error: '', method: 'mail' });
    expect(sink.messages).toHaveLength(0);
    expect(api.requests).toHaveLength(0);
  });

  test('invalid configuration is refused, nothing is sent', async () => {
    let r = await send({ method: 'carrier-pigeon' }, true);
    expect(r.ok).toBe(false);
    expect(r.error).toContain('not supported');
    r = await send({ method: 'api', api_key: '' }, true);
    expect(r.ok).toBe(false);
    expect(r.error).toContain('API key is missing');
    r = await send({ method: 'api', api_key: 'SG.x', api_provider: 'mailchimp' }, true);
    expect(r.ok).toBe(false);
    expect(r.error).toContain('not supported');
    r = await send({ method: 'smtp', smtp_host: '' }, true);
    expect(r.ok).toBe(false);
    expect(r.error).toContain('SMTP settings are incomplete');
    expect(sink.messages).toHaveLength(0);
    expect(api.requests).toHaveLength(0);
  });

  test('Sitepanel enquiry Send Mail: "Email sent successfully."', async ({ browser }) => {
    test.skip(!process.env.SP_USER, 'needs SP_USER / SP_PASS');
    // The 8081 server sends with the .env method (mail): PHP mail() to localhost:25 (php.ini).
    const method = 'mail';
    const email = `pw-test+enq-${unique()}@example.com`;
    sql(`insert into wl_enquiry (first_name, last_name, email, message, receive_date, reply_status) values ('Pwtest', 'Enquirer', '${email}', 'PW-TEST enquiry', now(), 'N')`);
    const [[id]] = sql(`select id from wl_enquiry where email='${email}'`);
    const ctx = await browser.newContext({ baseURL: ADMIN_URL });
    const page = await ctx.newPage();
    const log = watch(page);
    try {
      await page.goto('sitepanel/login');
      await page.click('#username');
      await expect(page.locator('#username')).toBeEditable();
      await page.fill('#username', process.env.SP_USER);
      await page.fill('#password', process.env.SP_PASS);
      await page.click('button[type="submit"]');
      await expect(page).toHaveURL(/sitepanel\/dashboard/);

      const compose = async () => {
        // The test enquiry's own row only (real enquiries in the list are never touched).
        await page.goto(`sitepanel/enquiry?keyword=${encodeURIComponent(email)}`);
        const row = page.locator('tbody tr', { hasText: email });
        await expect(row).toHaveCount(1);
        await row.locator('button', { hasText: 'Send Mail' }).click();
        await page.fill('input[name="subject"]', 'PW-TEST reply');
        await page.waitForFunction(() => window.CKEDITOR && Object.values(CKEDITOR.instances).some((i) => i.status === 'ready'), null, { timeout: 20_000 });
        await page.evaluate(() => {
          const t = document.querySelector('textarea[name="message"]');
          if (window.CKEDITOR && CKEDITOR.instances[t.id]) CKEDITOR.instances[t.id].setData('<p>PW-TEST reply body</p>');
          else t.value = '<p>PW-TEST reply body</p>';
        });
        await page.locator('form[action$="preview_mail"] button[type="submit"]').click(); // Preview Email
        await page.locator('form[action$="deliver_mail"] button[type="submit"]').click();  // Send Mail
      };

      // PHP mail() is not checked: with nothing on localhost:25 the page still shows the success message.
      await compose();
      await expect(page.locator('.alert-success').first()).toHaveText('Email sent successfully.');
      await expect(page.locator('.alert-danger')).toHaveCount(0);
      await expect(page.locator('body')).not.toContainText(/via PHP mail|email\(s\) sent \(via|could not be sent/);

      // Success: a sink on localhost:25 receives exactly one message.
      const port25 = await smtpSink(25);
      try {
        await compose();
        await expect(page.locator('.alert-success').first()).toHaveText('Email sent successfully.');
        await expect(page.locator('body')).not.toContainText(/via PHP mail|email\(s\) sent/);
        expect(port25.messages).toHaveLength(1);
        expect(port25.messages[0].to.join(' ')).toContain(email);
        expect(sink.messages.length + api.requests.length, `only the ${method} transport`).toBe(0);
        expect(sql(`select reply_status from wl_enquiry where id=${id}`)[0][0]).toBe('Y');
      } finally {
        await port25.close();
      }
      expectClean(log);
    } finally {
      sql(`delete from wl_enquiry where id=${id}`);
      await ctx.close();
    }
  });
});
