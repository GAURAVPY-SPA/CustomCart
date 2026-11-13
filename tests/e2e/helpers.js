// Shared checks for every spec: browser console errors, failed requests,
// PHP error text, previous-project branding, plus the captcha reader.
const fs = require('fs');
const path = require('path');
const { expect } = require('@playwright/test');

const PROJECT_ROOT = path.resolve(__dirname, '..', '..');
const SESSION_DIR = path.join(PROJECT_ROOT, 'writable', 'session');

// Text of the project this one was started from (must not appear on any page).
const OLD_BRAND = /IDRAK|AI\s*Automation|Automation\s*Agency|aiautomation|kareem\s*tex|yuan\s*shi\s*dian|\bYSD\b|CordialRide|kareem_shield/i;

// PHP / CodeIgniter error output that must never reach a page.
const PHP_ERROR = /(Undefined (variable|index|array key|offset|property)|Fatal error|Parse error|<b>Warning<\/b>|<b>Notice<\/b>|<b>Deprecated<\/b>|Uncaught |ErrorException|Whoops!|Call to (a member function|undefined)|TypeError:|ArgumentCountError|DivisionByZeroError|Trying to access array offset)/;

// Third-party hosts whose failures are reported as warnings, not test failures
// (maps, video embeds and fonts depend on the network, not on this application).
const EXTERNAL_OK = /(google|gstatic|youtube|ytimg|doubleclick|jsdelivr|googleapis|facebook|twitter|x\.com|linkedin|instagram|cdnjs|unpkg)/i;

/**
 * Collects console errors, page errors, failed same-origin requests and
 * HTTP >= 400 responses while a test runs.
 */
function watch(page) {
  const log = { console: [], pageErrors: [], failed: [], badStatus: [], external: [], alerts: [] };
  const origin = () => { try { return new URL(page.url()).origin; } catch { return ''; } };

  page.on('console', (msg) => {
    if (msg.type() !== 'error') return;
    const text = msg.text();
    const where = msg.location()?.url || '';
    if (EXTERNAL_OK.test(where) || EXTERNAL_OK.test(text)) { log.external.push(text); return; }
    // The browser logs a console error for every failed resource; those are reported via badStatus.
    if (/Failed to load resource/.test(text)) return;
    log.console.push(text);
  });
  page.on('pageerror', (err) => log.pageErrors.push(String(err.message || err)));
  // An alert() is how the site's scripts report a failed background request.
  // (confirm() dialogs are left to the test that expects them.)
  page.on('dialog', async (d) => {
    if (d.type() !== 'alert') return;
    log.alerts.push(d.message());
    await d.dismiss().catch(() => {});
  });
  page.on('requestfailed', (req) => {
    const failure = req.failure()?.errorText || '';
    if (/ERR_ABORTED|NS_BINDING_ABORTED/.test(failure)) return; // navigation away / media range requests
    (EXTERNAL_OK.test(req.url()) ? log.external : log.failed).push(`${failure} ${req.url()}`);
  });
  page.on('response', (res) => {
    if (res.status() < 400) return;
    const url = res.url();
    if (EXTERNAL_OK.test(url) || (origin() && !url.startsWith(origin()))) { log.external.push(`${res.status()} ${url}`); return; }
    log.badStatus.push(`${res.status()} ${url}`);
  });
  return log;
}

/** Fails the test when the watcher saw application errors. */
function expectClean(log, { allowStatus = [] } = {}) {
  const bad = log.badStatus.filter((s) => !allowStatus.some((a) => s.includes(a)));
  expect.soft(log.pageErrors, 'JavaScript page errors').toEqual([]);
  expect.soft(log.console, 'console errors').toEqual([]);
  expect.soft(log.alerts, 'alert() messages').toEqual([]);
  expect.soft(log.failed, 'failed requests').toEqual([]);
  expect.soft(bad, 'HTTP >= 400 responses').toEqual([]);
}

/** Checks the rendered HTML for PHP errors and previous-project branding. */
async function expectSaneHtml(page) {
  const html = await page.content();
  expect.soft(html.match(PHP_ERROR)?.[0] ?? null, `PHP error text on ${page.url()}`).toBeNull();
  expect.soft(html.match(OLD_BRAND)?.[0] ?? null, `previous-project branding on ${page.url()}`).toBeNull();
}

/** Opens a page and runs the standard checks; returns the response. */
async function visit(page, url, { status = 200 } = {}) {
  const res = await page.goto(url, { waitUntil: 'load' });
  expect(res, `no response for ${url}`).not.toBeNull();
  expect.soft(res.status(), `HTTP status of ${url}`).toBe(status);
  await expectSaneHtml(page);
  return res;
}

/** The code of a public form's captcha, read from the PHP session file. */
async function captchaCode(page, form) {
  await page.waitForFunction(() => {
    const img = document.querySelector('img[data-captcha-image]');
    return img && img.complete && img.naturalWidth > 0;
  });
  const cookies = await page.context().cookies();
  const session = cookies.find((c) => /_session$/.test(c.name));
  if (!session) throw new Error('No session cookie: cannot read the captcha code');
  const file = path.join(SESSION_DIR, session.name + session.value);
  for (let i = 0; i < 20; i++) {
    const data = fs.existsSync(file) ? fs.readFileSync(file, 'utf8') : '';
    const m = data.match(new RegExp(`captcha_${form}\\|a:2:\\{s:4:"code";s:\\d+:"([A-Z0-9]+)"`));
    if (m) return m[1];
    await page.waitForTimeout(150);
  }
  throw new Error(`Captcha code for "${form}" not found in ${file}`);
}

/** Whether the page scrolls sideways (layout overflow). */
async function horizontalOverflow(page) {
  return page.evaluate(() => {
    const w = document.documentElement.clientWidth;
    const offenders = [];
    for (const el of document.querySelectorAll('body *')) {
      const r = el.getBoundingClientRect();
      if (r.width && r.right > w + 1 && getComputedStyle(el).position !== 'fixed') {
        if (!el.closest('.modal, .offcanvas, [aria-hidden="true"], .hero-track, .swiper, .carousel, .marquee, .partner-track, .logos-track')) offenders.push(`${el.tagName.toLowerCase()}.${[...el.classList].join('.')}`);
      }
      if (offenders.length > 5) break;
    }
    return { scrollWidth: document.documentElement.scrollWidth, width: w, offenders };
  });
}

const unique = () => `${Date.now().toString(36)}${Math.random().toString(36).slice(2, 6)}`;

// Database checks (read what a form saved; remove test records). Local MySQL CLI.
const MYSQL = process.env.MYSQL_BIN || 'D:/wamp64/bin/mysql/mysql8.4.7/bin/mysql.exe';
const DB_NAME = process.env.DB_NAME || 'customcart';
function sql(query) {
  const { execFileSync } = require('child_process');
  const out = execFileSync(MYSQL, ['-uroot', '-N', '-B', '--default-character-set=utf8mb4', DB_NAME, '-e', query], { encoding: 'utf8' });
  return out.split(/\r?\n/).filter(Boolean).map((line) => line.split('\t'));
}
const esc = (s) => String(s).replace(/\\/g, '\\\\').replace(/'/g, "\\'");

// A real image of the theme for upload tests.
const SAMPLE_IMAGE = path.join(PROJECT_ROOT, 'public', 'assets', 'designer', 'themes', 'default', 'images', 'product-image1.jpg');

module.exports = { OLD_BRAND, PHP_ERROR, PROJECT_ROOT, SAMPLE_IMAGE, watch, expectClean, expectSaneHtml, visit, captchaCode, horizontalOverflow, unique, sql, esc };
