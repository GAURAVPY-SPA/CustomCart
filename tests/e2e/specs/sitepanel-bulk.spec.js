// Sitepanel bulk action toolbars above and below every list table
// (includes/bulk_actions.php, 'position' => 'bottom'): both send the one bulk form
// (the bottom buttons use form="<that form's id>"), so the selected ids, the filters,
// the page, CSRF and the Delete confirmation are shared and one request is sent.
// Working cycle on 23 temporary PW-TEST FAQs (removed by id at the end); every other
// list page is only opened and checked, nothing on it is changed.
const { test, expect } = require('@playwright/test');
const { watch, expectClean, unique, sql } = require('../helpers');

const tag = unique();
const KEYWORD = `PW-TEST bulk ${tag}`;
const ids = [];

// Every routed Sitepanel list (as sitepanel-crawl.spec.js) one country's states and one state's cities;
// pages with a bulk form are checked for the two toolbars. Category has its own
// toolbar markup (one form around the table, actions above and below it).
const SECTIONS = ['blogs', 'blog_categories', 'blog_comments', 'testimonial', 'team', 'courses', 'course_enquiries', 'services', 'events',
  'news', 'stories', 'enquiry', 'product_enquiry', 'coupons', 'header_images', 'faq', 'gallery', 'video', 'subadmin', 'location', 'meta',
  'banners', 'staticpages', 'newsletter', 'category', 'products', 'color', 'size', 'brand', 'material', 'reviews', 'shipping', 'orders',
  'members', 'volunteers', 'order_products', 'reasons'];
function bulkPages() {
  const [country] = sql('select country_id from wl_countries order by country_id limit 1').map((r) => r[0]);
  const [state] = sql('select state_id from wl_states order by state_id limit 1').map((r) => r[0]);
  return [...SECTIONS.map((s) => `sitepanel/${s}`), ...(country ? [`sitepanel/location/states/${country}`] : []),
    ...(state ? [`sitepanel/location/cities/${state}`] : [])];
}
const success = (page) => page.locator('.alert-success, .sp-alert-success').first();

const status = (id) => sql(`select status from wl_faq where faq_id=${id}`)[0]?.[0];

test.describe.serial('sitepanel bulk toolbars', () => {
  test.beforeAll(() => {
    for (let i = 1; i <= 23; i++) {
      sql(`insert into wl_faq (faq_question, faq_answer, sort_order, status, faq_date_added) values ('${KEYWORD} ${String(i).padStart(2, '0')}', 'PW-TEST answer', ${9000 + i}, '1', now())`);
    }
    ids.push(...sql(`select faq_id from wl_faq where faq_question like '${KEYWORD} %' order by sort_order`).map((r) => Number(r[0])));
  });
  test.afterAll(() => {
    if (ids.length) sql(`delete from wl_faq where faq_id in (${ids.join(',')}) and faq_question like 'PW-TEST bulk %'`);
  });

  test('every list with bulk actions has the toolbar above and below the table', async ({ page }) => {
    test.setTimeout(5 * 60_000);
    const log = watch(page);
    let checked = 0;
    for (const url of bulkPages()) {
      await page.goto(url);
      const forms = page.locator('form[data-sp-bulk-form]');
      if (!(await forms.count())) continue;
      if (/sitepanel\/category/.test(url)) {
        // Category (the reference): one form around the table, actions above and below it.
        await expect(page.locator('.sp-category-bulk-actions'), url).toHaveCount(2);
        checked++;
        continue;
      }
      const form = forms.first();
      const id = await form.getAttribute('id');
      const rows = await page.locator(`[data-sp-select][form="${id}"]`).count();
      if (!rows) continue; // empty list: no table, no toolbars to compare
      const top = page.locator('.sp-bulk-toolbar:not(.sp-bulk-toolbar--bottom)');
      const bottom = page.locator('.sp-bulk-toolbar--bottom');
      await expect(top, url).toHaveCount(1);
      await expect(bottom, `${url} bottom toolbar`).toHaveCount(1);
      // Same buttons, same labels and values; the bottom ones send the top form and add no form or fields.
      const buttons = (loc) => loc.locator('button[name="bulk_action"]').evaluateAll((bs) => bs.map((b) => `${b.value}|${b.textContent.trim()}|${b.className}|${b.getAttribute('data-sp-confirm') || ''}`));
      expect(await buttons(bottom), url).toEqual(await buttons(top));
      expect(await bottom.locator('button[name="bulk_action"]').evaluateAll((bs) => bs.every((b) => b.form && b.form.id === b.getAttribute('form'))), url).toBe(true);
      expect(await bottom.locator('button[name="bulk_action"]').first().getAttribute('form'), url).toBe(id);
      await expect(bottom.locator('form, input'), url).toHaveCount(0);
      // Table between the two toolbars.
      const order = await page.evaluate(() => {
        const t = document.querySelector('.sp-bulk-toolbar:not(.sp-bulk-toolbar--bottom)');
        const tbl = document.querySelector('.sp-table-wrap');
        const b = document.querySelector('.sp-bulk-toolbar--bottom');
        return !!(t.compareDocumentPosition(tbl) & Node.DOCUMENT_POSITION_FOLLOWING) && !!(tbl.compareDocumentPosition(b) & Node.DOCUMENT_POSITION_FOLLOWING);
      });
      expect(order, `${url}: top toolbar, table, bottom toolbar`).toBe(true);
      // No duplicate ids on the page.
      const dupes = await page.evaluate(() => {
        const seen = {};
        document.querySelectorAll('[id]').forEach((e) => { seen[e.id] = (seen[e.id] || 0) + 1; });
        return Object.keys(seen).filter((k) => seen[k] > 1);
      });
      expect(dupes, url).toEqual([]);
      checked++;
    }
    expect(checked, 'list pages checked').toBeGreaterThanOrEqual(15);
    expectClean(log);
  });

  test('top and bottom toolbars on FAQs: activate, deactivate, select all, delete, filters and pages kept', async ({ page }) => {
    test.setTimeout(3 * 60_000);
    const log = watch(page);
    const posts = [];
    page.on('request', (r) => { if (r.method() === 'POST' && /sitepanel\/faq\/bulk$/.test(r.url())) posts.push(r.postData() || ''); });
    const dialogs = [];
    let answer = true;
    // Alerts are answered and recorded by watch() (log.alerts); confirmations here.
    page.on('dialog', (d) => { if (d.type() !== 'confirm') return; dialogs.push(`confirm:${d.message()}`); answer ? d.accept() : d.dismiss(); });
    const list = `sitepanel/faq?keyword=${encodeURIComponent(KEYWORD)}`;
    const box = (id) => page.locator(`[data-sp-select="faq"][value="${id}"]`);
    const top = (v) => page.locator(`.sp-bulk-toolbar:not(.sp-bulk-toolbar--bottom) button[value="${v}"]`);
    const bottom = (v) => page.locator(`.sp-bulk-toolbar--bottom button[value="${v}"]`);

    sql(`update wl_faq set status='0' where faq_id in (${ids.join(',')})`);
    await page.goto(list);
    await expect(page.locator('[data-sp-select="faq"]')).toHaveCount(20); // page 1 of 2

    // Nothing selected: either toolbar only warns, no request.
    for (const btn of [top('activate'), bottom('delete')]) {
      await btn.click();
      await expect.poll(() => log.alerts.length).toBeGreaterThan(0);
      expect(log.alerts.pop()).toBe('Select one or more records first.');
    }
    expect(dialogs, 'no Delete confirmation without a selection').toHaveLength(0);
    expect(posts).toHaveLength(0);

    // One record, top Active: only that one, one request, filter kept.
    await box(ids[0]).check();
    await top('activate').click();
    await expect(success(page)).toBeVisible();
    expect(posts).toHaveLength(1);
    expect(status(ids[0])).toBe('1');
    expect(status(ids[1])).toBe('0');
    expect(decodeURIComponent(page.url())).toContain(`keyword=${KEYWORD}`.replace(/ /g, '+'));

    // Several records, bottom Deactive (sends the same form: ids, keyword, page and CSRF).
    sql(`update wl_faq set status='1' where faq_id in (${ids.slice(0, 3).join(',')})`);
    await page.goto(list);
    for (const id of ids.slice(0, 3)) await box(id).check();
    await bottom('deactivate').click();
    await expect(success(page)).toBeVisible();
    expect(posts).toHaveLength(2);
    const sent = new URLSearchParams(posts[1]);
    expect(sent.getAll('ids[]').map(Number).sort((a, b) => a - b)).toEqual(ids.slice(0, 3).sort((a, b) => a - b));
    expect(sent.get('bulk_action')).toBe('deactivate');
    expect(sent.get('keyword')).toBe(KEYWORD);
    expect(sent.get('csrf_customcart')).toBeTruthy();
    expect(ids.slice(0, 3).map(status)).toEqual(['0', '0', '0']);
    expect(status(ids[3])).toBe('0');

    // Select All (one header checkbox) and the rows stay in step; the bottom toolbar sends them all.
    await page.goto(list);
    const all = page.locator('[data-sp-select-all="faq"]');
    await all.check();
    expect(await page.locator('[data-sp-select="faq"]:checked').count()).toBe(20);
    await box(ids[0]).uncheck();
    expect(await all.evaluate((e) => [e.checked, e.indeterminate])).toEqual([false, true]);
    await box(ids[0]).check();
    expect(await all.evaluate((e) => e.checked)).toBe(true);
    await bottom('activate').click();
    await expect(success(page)).toBeVisible();
    expect(posts).toHaveLength(3);
    expect(new URLSearchParams(posts[2]).getAll('ids[]')).toHaveLength(20);
    expect(ids.slice(0, 20).every((id) => status(id) === '1')).toBe(true);
    expect(ids.slice(20).every((id) => status(id) === '0')).toBe(true); // page 2 untouched

    // Page 2 with the filter: bottom toolbar acts on page 2 and comes back to it.
    await page.goto(`${list}&page=2`);
    await expect(page.locator('[data-sp-select="faq"]')).toHaveCount(3);
    await box(ids[21]).check();
    await bottom('activate').click();
    await expect(success(page)).toBeVisible();
    expect(page.url()).toMatch(/page=2/);
    expect(page.url()).toContain('keyword=');
    expect(status(ids[21])).toBe('1');

    // Delete from the top toolbar: cancelling the confirmation sends nothing; accepting deletes only the selection.
    await page.goto(list);
    await box(ids[4]).check();
    await box(ids[5]).check();
    answer = false;
    await top('delete').click();
    await expect.poll(() => dialogs.length).toBeGreaterThan(0);
    expect(dialogs.pop()).toMatch(/^confirm:Delete the selected FAQs\?/);
    expect(posts).toHaveLength(4);
    expect(Number(sql(`select count(*) from wl_faq where faq_id in (${ids[4]},${ids[5]})`)[0][0])).toBe(2);
    answer = true;
    await top('delete').click();
    await expect(success(page)).toBeVisible();
    expect(posts).toHaveLength(5);
    expect(Number(sql(`select count(*) from wl_faq where faq_id in (${ids[4]},${ids[5]})`)[0][0])).toBe(0);
    expect(Number(sql(`select count(*) from wl_faq where faq_id in (${ids.join(',')})`)[0][0])).toBe(21);

    // Delete from the bottom toolbar asks the same confirmation.
    await page.goto(list);
    await box(ids[6]).check();
    await bottom('delete').click();
    await expect(success(page)).toBeVisible();
    expect(dialogs.pop()).toMatch(/^confirm:Delete the selected FAQs\?/);
    expect(posts).toHaveLength(6);
    expect(Number(sql(`select count(*) from wl_faq where faq_id=${ids[6]}`)[0][0])).toBe(0);
    expectClean(log);
  });
});
