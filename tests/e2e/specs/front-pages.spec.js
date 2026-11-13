// Every page of the HTML design reference (html/*.htm), at its real route
// (wl_meta_tags), opened in a browser: status, header and footer, JS errors,
// failed requests, PHP errors, old branding. Account pages are covered by
// account-*.spec.js; pages that need an order are listed as skipped there.
const { test, expect } = require('@playwright/test');
const { watch, expectClean, expectSaneHtml, sql } = require('../helpers');

const first = (q) => { try { return sql(q)[0]?.[0] || null; } catch { return null; } };
const detail = (type) => first(`select m.page_url from wl_meta_tags m where m.entity_type='${type}' and m.status='1' order by m.meta_id limit 1`);
const activeTeam = first(`select m.page_url from wl_meta_tags m join wl_team t on t.team_id=m.entity_id where m.entity_type='team/detail' and t.status='1' limit 1`);
const product = first(`select m.page_url from wl_meta_tags m join wl_products p on p.products_id=m.entity_id where m.entity_type='products/detail' and p.status='1' limit 1`)
  || detail('products/detail');
const topCategory = first(`select page_url from wl_meta_tags where entity_type='category/index' and page_url not like '%/%' limit 1`);
const subCategory = first(`select page_url from wl_meta_tags where entity_type='category/index' and page_url like '%/%' limit 1`);

// [design file, route, expected status]
const PAGES = [
  ['index.htm', '', 200],
  ['404.htm', 'this-page-does-not-exist-pw', 404],
  ['mission-and-Story.htm', 'mission-and-story', 200],
  ['yuan-shi-dian-dr-zhang.htm', 'yuan-shi-dian-dr-zhang', 200],
  ['cch-Foundation.htm', 'cch-foundation', 200],
  ['southeast-asia-original-point-promotion-office.htm', 'southeast-asia-original-point-promotion-office', 200],
  ['southeast-asia-details.htm', 'singapore-zhang-zhaohan-promotion-center', 200],
  ['our-team.htm', 'our-team', 200],
  ['our-team-details.htm', activeTeam, 200],
  ['learning-courses.htm', 'learning-courses', 200],
  ['experience-services.htm', 'experience-services', 200],
  ['community-activities.htm', 'community-activities', 200],
  ['resources-publications.htm', 'resources-publications', 200],
  ['events.htm', 'events-activities', 200],
  ['events-details.htm', detail('events/detail'), 200],
  ['news.htm', 'news-announcements', 200],
  ['news-details.htm', detail('news/detail'), 200],
  ['blog.htm', 'blogs', 200],
  ['(blog categories)', 'blogs/categories', 200],
  ['blog-details.htm', detail('blog/detail'), 200],
  ['success-stories.htm', 'success-stories', 200],
  ['success-storie-details.htm', detail('stories/detail'), 200],
  ['testimonials.htm', 'testimonials', 200],
  ['images-gallery.htm', 'images-gallery', 200],
  ['videos-gallery.htm', 'videos-gallery', 200],
  ['category.htm', 'online-store', 200],
  ['category.htm (category)', topCategory, 200],
  ['sub-category.htm', subCategory, 200],
  ['listing.htm', 'products', 200],
  ['details.htm', product, 200],
  ['cart.htm', 'cart', 200],
  ['donations.htm', 'donations', 200],
  ['contact-us.htm', 'contactus', 200],
  ['(about us)', 'aboutus', 200],
  ['privacy-policy.htm', 'privacy-policy', 200],
  ['refund-policy.htm', 'refund-policy', 200],
  ['terms-of-use.htm', 'terms-conditions', 200],
  ['sitemap.htm', 'sitemap', 200],
  ['login.htm', 'login', 200],
  ['(register)', 'register', 200],
  ['(forgot password)', 'forgot-password', 200],
  ['volunteer-training-registration.htm', 'volunteer-training-registration', 200],
];

for (const [design, route, status] of PAGES) {
  test(`${design} -> /${route ?? '(no record)'}`, async ({ page }) => {
    test.skip(route === null, `no active record for ${design}`);
    const log = watch(page);
    const res = await page.goto(route, { waitUntil: 'load' });
    expect.soft(res.status(), `status of /${route}`).toBe(status);
    await expectSaneHtml(page);
    await expect.soft(page.locator('header, .site-wrap-header').first(), 'site header').toBeVisible();
    await expect.soft(page.locator('footer').first(), 'site footer').toBeAttached();
    await expect.soft(page.locator('h1').first(), 'page heading').toBeAttached();
    const title = await page.title();
    expect.soft(title.trim(), 'title').not.toBe('');
    // Images on the page load (lazy images scrolled into view first).
    await page.evaluate(async () => { for (let y = 0; y < document.body.scrollHeight; y += 700) { window.scrollTo(0, y); await new Promise((r) => setTimeout(r, 40)); } });
    await page.waitForLoadState('networkidle').catch(() => {});
    const broken = await page.$$eval('img', (imgs) => imgs.filter((i) => i.currentSrc && i.complete && i.naturalWidth === 0 && !/youtube|ytimg|google/.test(i.currentSrc)).map((i) => i.currentSrc));
    expect.soft(broken, 'broken images').toEqual([]);
    expectClean(log, { allowStatus: status === 404 ? [`/${route}`] : [] });
  });
}

test('header navigation: every menu link opens a page', async ({ page, request }) => {
  const log = watch(page);
  await page.goto('');
  const links = await page.$$eval('header a[href], .site-wrap-header a[href]', (as) => [...new Set(as.map((a) => a.href))]);
  expect(links.length).toBeGreaterThan(15);
  const bad = [];
  for (const href of links) {
    if (!href.startsWith(page.url().replace(/#.*$/, '').replace(/\/$/, '')) || /#$/.test(href)) continue;
    const res = await request.get(href);
    if (res.status() >= 400) bad.push(`${res.status()} ${href}`);
  }
  expect(bad).toEqual([]);
  // Main menu opens on hover; the Register menu stays open when clicked right after the hover opened it.
  await page.locator('.main-nav > .menu-item').first().hover();
  await expect(page.locator('.main-nav > .menu-item > .menu-link').first()).toHaveAttribute('aria-expanded', 'true');
  await expect(page.locator('.main-nav > .menu-item > .menu-panel').first()).toBeVisible();
  await page.hover('#registerToggle');
  await page.click('#registerToggle');
  await expect(page.locator('#registerToggle')).toHaveAttribute('aria-expanded', 'true');
  await expect(page.locator('.register-menu')).toBeVisible();
  expectClean(log);
});

test('footer links open pages', async ({ page, request }) => {
  await page.goto('');
  const links = await page.$$eval('footer a[href]', (as) => [...new Set(as.map((a) => a.href))]);
  const base = page.url().replace(/#.*$/, '');
  const bad = [];
  for (const href of links.filter((h) => h.startsWith(base))) {
    const res = await request.get(href);
    if (res.status() >= 400) bad.push(`${res.status()} ${href}`);
  }
  expect(links.length).toBeGreaterThan(10);
  expect(bad).toEqual([]);
});

test('sitemap: every link opens a page', async ({ page, request }) => {
  await page.goto('sitemap');
  const links = await page.$$eval('main a[href]', (as) => [...new Set(as.map((a) => a.href))]);
  const base = new URL(page.url()).origin;
  const bad = [];
  for (const href of links.filter((h) => h.startsWith(base) && !/logout/.test(h))) {
    const res = await request.get(href);
    if (res.status() >= 400) bad.push(`${res.status()} ${href}`);
  }
  expect(links.length).toBeGreaterThan(20);
  expect(bad).toEqual([]);
});

test('listing pages link to their detail pages', async ({ page }) => {
  const log = watch(page);
  for (const [listing, prefix] of [['events-activities', 'events-activities/'], ['news-announcements', 'news-announcements/'], ['blogs', 'blogs/'], ['success-stories', 'success-stories/'], ['our-team', 'our-team/']]) {
    await page.goto(listing);
    const link = page.locator(`main a[href*="/${prefix}"]`).first();
    await expect(link, `${listing} has a detail link`).toBeAttached();
    const href = await link.getAttribute('href');
    const res = await page.goto(href);
    expect.soft(res.status(), href).toBe(200);
    await expectSaneHtml(page);
  }
  expectClean(log);
});
