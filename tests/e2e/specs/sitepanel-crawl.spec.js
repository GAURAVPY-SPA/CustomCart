// Every Sitepanel page reachable by safe GET links from the dashboard and
// sidebar (listings, add/edit/view forms, pagination, search links), in a
// browser: HTTP status, PHP errors, old branding, JS errors, failed requests.
// Never follows actions (status, delete, bulk, send, logout ...).
const fs = require('fs');
const path = require('path');
const { test, expect } = require('@playwright/test');
const { watch, OLD_BRAND, PHP_ERROR } = require('../helpers');

// Last path segment(s) allowed after sitepanel/<section>/
const SAFE_ACTIONS = new Set(['', 'index', 'add', 'edit', 'view', 'cities', 'city_add', 'city_edit', 'stock', 'media', 'related',
  'invoice', 'compose_mail', 'preview_mail', 'compose_email', 'preview_email', 'template']);
const MAX_PAGES = 400;
const MAX_VARIANTS = 3;

function safe(url, base) {
  const u = new URL(url);
  if (!u.href.startsWith(new URL('sitepanel/', base).href)) return false;
  const parts = u.pathname.replace(/^\/+/, '').split('/'); // sitepanel, section, action, id
  if (parts[1] === 'logout' || parts[1] === 'login') return false;
  return SAFE_ACTIONS.has(parts[2] || '');
}

test('sitepanel crawl: every listing and form page', async ({ page, baseURL }) => {
  test.setTimeout(20 * 60_000);
  const results = [];
  // Dashboard first; then every routed section (modules/sitepanel/Config/Routes.php),
  // including the ones the sidebar hides on purpose, so direct access is checked too.
  const SECTIONS = ['settings', 'theme', 'change_password', 'blogs', 'blog_categories', 'blog_comments', 'testimonial', 'team', 'courses',
    'course_enquiries', 'services', 'events', 'news', 'stories', 'enquiry', 'product_enquiry', 'coupons', 'header_images', 'faq', 'gallery',
    'video', 'subadmin', 'location', 'meta', 'banners', 'staticpages', 'newsletter', 'category', 'products', 'color', 'size', 'brand',
    'material', 'reviews', 'shipping', 'orders', 'members', 'volunteers', 'products_bulk', 'order_products', 'reasons'];
  const queue = [new URL('sitepanel/dashboard', baseURL).href, ...SECTIONS.map((s) => new URL(`sitepanel/${s}`, baseURL).href)];
  const seen = new Set();
  const variants = new Map();
  const perSection = new Map(); // keep the crawl to a few edit/view pages per section

  while (queue.length && results.length < MAX_PAGES) {
    const url = queue.shift();
    if (seen.has(url)) continue;
    seen.add(url);
    const u = new URL(url);
    if (u.search) {
      const n = (variants.get(u.pathname) || 0) + 1;
      variants.set(u.pathname, n);
      if (n > MAX_VARIANTS) continue;
    }
    const parts = u.pathname.replace(/^\/+/, '').split('/');
    if (parts[3]) { // an item page: at most 2 per section/action
      const key = `${parts[1]}/${parts[2]}`;
      const n = (perSection.get(key) || 0) + 1;
      perSection.set(key, n);
      if (n > 2) continue;
    }

    const head = await page.request.get(url, { maxRedirects: 0 }).catch((e) => ({ status: () => 0, headers: () => ({}), error: e }));
    const type = head.headers()['content-type'] || '';
    const entry = { url, status: head.status(), problems: [] };
    results.push(entry);
    if (entry.status >= 300 && entry.status < 400) { entry.redirect = head.headers().location; continue; }
    if (!type.includes('text/html')) continue; // downloads (e.g. the bulk-upload template)

    const log = watch(page);
    const res = await page.goto(url, { waitUntil: 'load' }).catch((e) => { entry.problems.push(`navigation: ${e.message}`); return null; });
    if (!res) continue;
    entry.status = res.status();
    if (/sitepanel\/login/.test(page.url())) { entry.problems.push('redirected to login (session lost)'); break; }
    await page.waitForTimeout(150);
    const html = await page.content();
    const php = html.match(PHP_ERROR);
    if (php) entry.problems.push(`PHP error text: ${php[0]}`);
    const brand = html.match(OLD_BRAND);
    if (brand) entry.problems.push(`old branding: ${brand[0]}`);
    for (const e of [...log.pageErrors, ...log.console]) entry.problems.push(`JS: ${e}`);
    for (const e of [...log.failed, ...log.badStatus]) entry.problems.push(`request: ${e}`);
    page.removeAllListeners('console'); page.removeAllListeners('pageerror'); page.removeAllListeners('requestfailed'); page.removeAllListeners('response');

    const links = await page.$$eval('a[href]', (as) => as.map((a) => a.href));
    for (const href of links) {
      try {
        const n = new URL(href); n.hash = '';
        if (!seen.has(n.href) && safe(n.href, baseURL)) queue.push(n.href);
      } catch { /* ignore */ }
    }
  }

  fs.mkdirSync(path.join(__dirname, '..', 'test-results'), { recursive: true });
  fs.writeFileSync(path.join(__dirname, '..', 'test-results', 'sitepanel-crawl.json'), JSON.stringify(results, null, 1));
  const bad = results.filter((r) => r.status >= 400 || r.status === 0 || r.problems.length).map((r) => `${r.status} ${r.url} ${r.problems.join(' | ')}`);
  console.log(`Sitepanel pages visited: ${results.length}; with problems: ${bad.length}`);
  expect.soft(bad, 'Sitepanel pages with errors').toEqual([]);
  expect(results.length).toBeGreaterThan(40);
});
