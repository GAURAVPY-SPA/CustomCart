// 404 audit of the website: every wl_meta_tags address plus every internal link
// and asset (CSS, JS, images) found on those pages, fetched without a browser.
// Writes test-results/front-crawl.json (every URL, its status and where it was linked from).
const fs = require('fs');
const path = require('path');
const { execFileSync } = require('child_process');
const { test, expect } = require('@playwright/test');
const { OLD_BRAND, PHP_ERROR } = require('../helpers');

const MYSQL = process.env.MYSQL_BIN || 'D:/wamp64/bin/mysql/mysql8.4.7/bin/mysql.exe';
const DB = process.env.DB_NAME || 'customcart';

// Never requested: actions (POST-only or with side effects) and the static design copy.
const SKIP = /\/(logout|cart\/add|cart\/update|cart\/remove|wishlist\/(add|remove))(\b|\/|$)|\/html\/|\/1html\//;
const MAX_PAGES = 600;
const MAX_QUERY_VARIANTS = 4; // per path, e.g. ?page=2, ?cat=x

function metaUrls() {
  try {
    const out = execFileSync(MYSQL, ['-uroot', '-N', DB, '-e', "select page_url from wl_meta_tags where status='1' or status is null"], { encoding: 'utf8' });
    return out.split(/\r?\n/).map((s) => s.trim()).filter(Boolean).map((u) => (u === 'home' ? '' : u));
  } catch (e) {
    console.warn('Could not read wl_meta_tags:', e.message);
    return [];
  }
}

test('crawl: every page, link and asset of the website', async ({ request, baseURL }) => {
  test.setTimeout(20 * 60_000);
  const base = new URL(baseURL);
  const queue = [{ url: base.href, from: '(start)' }];
  for (const u of ['sitemap', ...metaUrls()]) queue.push({ url: new URL(u, base).href, from: 'wl_meta_tags' });

  const seen = new Map(); // url -> { status, final, from, kind, problems }
  const variants = new Map();
  const assets = new Map();

  const norm = (href, pageUrl) => {
    try {
      const u = new URL(href, pageUrl);
      u.hash = '';
      return u;
    } catch { return null; }
  };

  while (queue.length && [...seen.values()].filter((v) => v.kind === 'page').length < MAX_PAGES) {
    const { url, from } = queue.shift();
    if (seen.has(url) || SKIP.test(url)) continue;
    const u = new URL(url);
    if (u.search) {
      const n = (variants.get(u.pathname) || 0) + 1;
      variants.set(u.pathname, n);
      if (n > MAX_QUERY_VARIANTS) continue;
    }
    const entry = { kind: 'page', from, status: 0, final: '', problems: [] };
    seen.set(url, entry);
    let res;
    try {
      res = await request.get(url, { maxRedirects: 5, timeout: 30_000 });
    } catch (e) {
      entry.problems.push(`request failed: ${e.message}`);
      continue;
    }
    entry.status = res.status();
    entry.final = res.url();
    const type = res.headers()['content-type'] || '';
    if (!type.includes('text/html')) { entry.kind = 'file'; continue; }
    const html = await res.text();
    const php = html.match(PHP_ERROR);
    if (php) entry.problems.push(`PHP error text: ${php[0]}`);
    const brand = html.match(OLD_BRAND);
    if (brand) entry.problems.push(`old branding: ${brand[0]}`);
    if (!entry.final.startsWith(base.href)) continue; // redirected off-site

    for (const m of html.matchAll(/<a\b[^>]*\bhref="([^"]+)"/gi)) {
      const href = m[1].replace(/&amp;/g, '&');
      if (/^(mailto:|tel:|javascript:|#|data:|whatsapp:|sms:)/i.test(href)) continue;
      const n = norm(href, entry.final);
      if (!n || !n.href.startsWith(base.href)) continue;
      if (!seen.has(n.href)) queue.push({ url: n.href, from: url });
    }
    for (const m of html.matchAll(/<(?:img|script|source|link|iframe|video)\b[^>]*\b(?:src|href|data-src|poster)="([^"]+)"/gi)) {
      const href = m[1].replace(/&amp;/g, '&');
      if (/^(data:|#|mailto:)/i.test(href)) continue;
      const n = norm(href, entry.final);
      if (!n || !n.href.startsWith(base.href) || SKIP.test(n.href)) continue;
      if (!assets.has(n.href)) assets.set(n.href, { from: url });
    }
    for (const m of html.matchAll(/url\((['"]?)([^'")]+)\1\)/gi)) { // inline style background images
      const n = norm(m[2], entry.final);
      if (n && n.href.startsWith(base.href) && !assets.has(n.href)) assets.set(n.href, { from: url });
    }
  }

  for (const [url, a] of assets) {
    if (seen.has(url)) { a.status = seen.get(url).status; continue; }
    try {
      const res = await request.get(url, { maxRedirects: 3, timeout: 30_000 });
      a.status = res.status();
    } catch (e) { a.status = 0; a.error = e.message; }
  }

  const pages = [...seen].filter(([, v]) => v.kind === 'page');
  // A wl_meta_tags row nothing links to may 404 on purpose (an inactive record,
  // or order-confirmation without an order): reported, not failed.
  const isBad = (status) => status >= 400 || status === 0;
  const unlinkedMeta = [...seen].filter(([, v]) => isBad(v.status) && v.from === 'wl_meta_tags').map(([u, v]) => `${v.status} ${u}`);
  const broken = [
    ...[...seen].filter(([, v]) => isBad(v.status) && v.from !== 'wl_meta_tags').map(([u, v]) => `${v.status} ${u}  (linked from ${v.from})`),
    ...[...assets].filter(([u, a]) => isBad(a.status) && u !== a.from).map(([u, a]) => `${a.status} ${u}  (asset on ${a.from})`),
  ];
  if (unlinkedMeta.length) console.log(`wl_meta_tags addresses not linked from any page that return an error (expected for inactive records):\n  ${unlinkedMeta.join('\n  ')}`);
  const problems = [...seen].filter(([, v]) => v.problems.length).map(([u, v]) => `${u}: ${v.problems.join('; ')}`);

  fs.mkdirSync(path.join(__dirname, '..', 'test-results'), { recursive: true });
  fs.writeFileSync(path.join(__dirname, '..', 'test-results', 'front-crawl.json'), JSON.stringify({
    pages: pages.length, assets: assets.size, broken, unlinkedMeta, problems,
    urls: Object.fromEntries(seen), assetStatus: Object.fromEntries(assets),
  }, null, 1));
  console.log(`Crawled ${pages.length} pages and ${assets.size} assets; ${broken.length} broken, ${problems.length} with problems.`);

  expect.soft(broken, 'broken links / assets (404 audit)').toEqual([]);
  expect.soft(problems, 'pages with PHP errors or old branding').toEqual([]);
  expect(pages.length).toBeGreaterThan(50);
});
