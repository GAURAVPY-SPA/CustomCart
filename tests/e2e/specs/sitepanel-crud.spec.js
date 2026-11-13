// Sitepanel functions with safe test records (titles start with "PW-TEST"), removed again:
// access restriction, login errors, validation, add with image upload, search,
// edit, status, delete, CKEditor + wl_meta_tags routing (News), pagination, logout.
const { test, expect } = require('@playwright/test');
const { watch, expectClean, expectSaneHtml, unique, sql, esc, SAMPLE_IMAGE } = require('../helpers');

const FRONT_URL = process.env.FRONT_URL || 'http://localhost/customcart/';
const flash = (page) => page.locator('.alert-success, .sp-alert-success, [role="status"].alert, .alert.alert-success');
const errors = (page) => page.locator('.invalid-feedback:visible, .alert-danger, .sp-alert-danger, .text-danger:visible');

test.describe('access', () => {
  test.use({ storageState: { cookies: [], origins: [] } });

  test('pages need a signed-in admin', async ({ page }) => {
    for (const url of ['sitepanel/dashboard', 'sitepanel/news/edit/1', 'sitepanel/settings', 'sitepanel/members']) {
      await page.goto(url);
      await expect(page, `${url} without login`).toHaveURL(/sitepanel\/login/);
    }
  });

  test('wrong login shows an error and stays on the login page', async ({ page }) => {
    await page.goto('sitepanel/login');
    await page.click('#username');
    await expect(page.locator('#username')).toBeEditable();
    await page.fill('#username', `nobody_${unique()}`);
    await page.fill('#password', 'WrongPass123!');
    await page.click('button[type="submit"]');
    await expect(page).toHaveURL(/sitepanel\/login/);
    await expect(page.locator('.alert, .invalid-feedback, [role="alert"]').first()).toBeVisible();
  });

  test('empty login is rejected', async ({ page }) => {
    await page.goto('sitepanel/login');
    await page.evaluate(() => document.querySelectorAll('[required]').forEach((e) => e.removeAttribute('required')));
    await page.click('button[type="submit"]');
    await expect(page).toHaveURL(/sitepanel\/login/);
    await expect(page.locator('.alert, .invalid-feedback, [role="alert"]').first()).toBeVisible();
  });
});

test.describe('gallery: add, upload, search, edit, status, delete', () => {
  const title = `PW-TEST gallery ${unique()}`;

  test.afterAll(() => { sql(`delete from wl_gallery where gallery_title like 'PW-TEST gallery%'`); });

  test('full cycle', async ({ page }) => {
    const log = watch(page);
    page.on('dialog', (d) => d.accept());

    // Required fields are enforced server-side (browser validation switched off).
    await page.goto('sitepanel/gallery/add');
    await page.evaluate(() => document.querySelectorAll('form [required]').forEach((e) => e.removeAttribute('required')));
    await page.locator('form[action$="gallery/add"] button[type="submit"]').click();
    await expect(page).toHaveURL(/gallery\/add/);
    await expect(errors(page).first()).toBeVisible();

    // Add with an image.
    await page.fill('input[name="gallery_title"]', title);
    await page.setInputFiles('input[name="gallery_image"]', SAMPLE_IMAGE);
    await page.locator('form[action$="gallery/add"] button[type="submit"]').click();
    await expect(page).toHaveURL(/sitepanel\/gallery(\?|$)/);
    await expect(flash(page).first()).toBeVisible();
    const [[id, image, status]] = sql(`select gallery_id, gallery_image, status from wl_gallery where gallery_title='${esc(title)}'`);
    expect(image).not.toBe('');

    // Search finds it and its thumbnail loads.
    await page.goto(`sitepanel/gallery?keyword=${encodeURIComponent(title)}`);
    const row = page.locator('tr', { hasText: title });
    await expect(row).toHaveCount(1);
    const img = row.locator('img').first();
    await expect(img).toBeVisible();
    // Lazy image served by the single-threaded spark server: wait for it to finish loading.
    await expect.poll(() => img.evaluate((i) => i.complete && i.naturalWidth > 0), { timeout: 15_000 }).toBe(true);

    // Uploaded file is served.
    const file = await page.request.get(new URL(`uploads/gallery/${image}`, FRONT_URL).href);
    expect(file.status()).toBe(200);

    // Edit.
    await page.goto(`sitepanel/gallery/edit/${id}`);
    await page.fill('input[name="gallery_title"]', `${title} edited`);
    await page.locator('form[action*="gallery/edit"] button[type="submit"]').click();
    await expect(flash(page).first()).toBeVisible();
    expect(sql(`select gallery_title from wl_gallery where gallery_id=${id}`)[0][0]).toBe(`${title} edited`);

    // Status change.
    await page.goto(`sitepanel/gallery?keyword=${encodeURIComponent(title)}`);
    await page.locator(`form[action$="gallery/status/${id}"] button`).click();
    await expect(flash(page).first()).toBeVisible();
    expect(sql(`select status from wl_gallery where gallery_id=${id}`)[0][0]).not.toBe(status);

    // Delete.
    await page.goto(`sitepanel/gallery?keyword=${encodeURIComponent(title)}`);
    await page.locator(`form[action$="gallery/delete/${id}"] button`).click();
    await expect(flash(page).first()).toBeVisible();
    expect(sql(`select count(*) from wl_gallery where gallery_id=${id}`)[0][0]).toBe('0');

    await expectSaneHtml(page);
    expectClean(log);
  });
});

test.describe('news: CKEditor and the wl_meta_tags address', () => {
  const title = `PW-TEST news ${unique()}`;

  test.afterAll(() => {
    for (const [id] of sql(`select news_id from wl_news where news_title like 'PW-TEST news%'`)) {
      sql(`delete from wl_meta_tags where entity_type='news/detail' and entity_id=${id}`);
      sql(`delete from wl_news where news_id=${id}`);
    }
  });

  test('add with the editor, open on the website, delete', async ({ page }) => {
    const log = watch(page);
    page.on('dialog', (d) => d.accept());
    await page.goto('sitepanel/news/add');
    await page.waitForFunction(() => window.CKEDITOR && Object.values(CKEDITOR.instances).some((i) => i.status === 'ready'), null, { timeout: 20_000 });
    await expect(page.locator('.cke').first()).toBeVisible();

    await page.fill('input[name="news_title"]', title);
    await page.fill('input[name="publish_date"]', '2026-11-05');
    await page.evaluate(() => Object.values(CKEDITOR.instances)[0].setData('<p>PW-TEST body text from the editor.</p>'));
    await page.selectOption('select[name="status"]', '1');
    await page.locator('form[action$="news/add"] button[type="submit"]').click();
    await expect(flash(page).first()).toBeVisible();

    const [[id, slug, body]] = sql(`select news_id, friendly_url, description from wl_news where news_title='${esc(title)}'`);
    expect(body).toContain('PW-TEST body text from the editor.');
    const meta = sql(`select page_url from wl_meta_tags where entity_type='news/detail' and entity_id=${id}`);
    expect(meta.length, 'wl_meta_tags row for the new item').toBe(1);

    const front = await page.request.get(new URL(meta[0][0], FRONT_URL).href);
    expect(front.status(), `website address ${meta[0][0]}`).toBe(200);
    expect(await front.text()).toContain('PW-TEST body text from the editor.');

    await page.goto(`sitepanel/news?keyword=${encodeURIComponent(title)}`);
    await page.locator(`form[action$="news/delete/${id}"] button`).click();
    await expect(flash(page).first()).toBeVisible();
    expect(sql(`select count(*) from wl_meta_tags where entity_type='news/detail' and entity_id=${id}`)[0][0]).toBe('0');
    const gone = await page.request.get(new URL(meta[0][0], FRONT_URL).href);
    expect(gone.status(), 'deleted item address').toBe(404);
    expect(slug).not.toBe('');
    expectClean(log);
  });
});

test('pagination and filters of a long listing', async ({ page }) => {
  const log = watch(page);
  await page.goto('sitepanel/orders');
  const next = page.locator('a.page-link', { hasText: /^\s*2\s*$/ }).first();
  await expect(next).toBeVisible();
  const firstPage = await page.locator('tbody tr').first().innerText();
  await next.click();
  await expect(page).toHaveURL(/page=2/);
  await expect(page.locator('tbody tr').first()).not.toHaveText(firstPage);
  await page.locator('a.page-link', { hasText: 'Last' }).first().click();
  await expect(page.locator('tbody tr').first()).toBeVisible();

  // Filters: each returns a page without errors; an impossible keyword lists nothing.
  for (const q of ['order_status=pending', 'payment_status=paid', 'from_date=2026-01-01&to_date=2026-12-31']) {
    const res = await page.goto(`sitepanel/orders?${q}`);
    expect(res.status(), q).toBe(200);
    await expectSaneHtml(page);
  }
  await page.goto('sitepanel/orders?keyword=zzzz-no-such-order');
  expect(await page.locator('tbody tr a[href*="orders/view"]').count()).toBe(0);
  expectClean(log);
});

test('settings save keeps the values', async ({ page }) => {
  const log = watch(page);
  await page.goto('sitepanel/settings');
  const before = await page.inputValue('#contact_phone');
  await page.locator('form button[type="submit"]').last().click();
  await expect(flash(page).first()).toBeVisible();
  await page.goto('sitepanel/settings');
  expect(await page.inputValue('#contact_phone')).toBe(before);
  expectClean(log);
});

test('logout ends the session', async ({ browser, baseURL }) => {
  // Its own login, so the shared session of the other tests stays signed in.
  const ctx = await browser.newContext({ baseURL, storageState: { cookies: [], origins: [] } });
  const page = await ctx.newPage();
  await page.goto('sitepanel/login');
  await page.click('#username');
  await expect(page.locator('#username')).toBeEditable();
  await page.fill('#username', process.env.SP_USER);
  await page.fill('#password', process.env.SP_PASS);
  await page.click('button[type="submit"]');
  await expect(page).toHaveURL(/sitepanel\/dashboard/);
  await page.locator('form[action$="sitepanel/logout"] button').first().evaluate((b) => b.closest('form').submit());
  await expect(page).toHaveURL(/sitepanel\/login/);
  await page.goto('sitepanel/dashboard');
  await expect(page).toHaveURL(/sitepanel\/login/);
  await ctx.close();
});
