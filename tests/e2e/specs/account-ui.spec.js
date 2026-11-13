// User and Volunteer account pages against the HTML design reference:
// screenshots of each reference page and its live page (desktop and mobile)
// in test-results/account-ui/, and the design classes of the reference's
// <main> that the live page does not use (test-results/account-ui/diff.json).
// Signs up one user and one volunteer (removed at the end).
const fs = require('fs');
const path = require('path');
const { test, expect } = require('@playwright/test');
const { captchaCode, unique, sql, horizontalOverflow } = require('../helpers');

const OUT = path.join(__dirname, '..', 'test-results', 'account-ui');
const REF = (file) => new URL(`html/${file}`, process.env.FRONT_URL || 'http://localhost/customcart/').href;
const id = unique();
const USER = { email: `pw-test+ui-user-${id}@example.com`, pass: 'UiPass123' };
const VOL = { email: `pw-test+ui-vol-${id}@example.com`, pass: 'UiPass123' };

const PAIRS = {
  user: [
    ['dashboard.htm', 'dashboard'],
    ['acc-order-history.htm', 'acc-order-history'],
    ['acc-event-booking-history.htm', 'acc-event-booking-history'],
    ['acc-edit.htm', 'acc-edit'],
    ['change-password.htm', 'change-password'],
  ],
  volunteer: [
    ['volunteer-dashboard.htm', 'volunteer-dashboard'],
    ['volunteer-course-booking.htm', 'volunteer-course-booking'],
    ['volunteer-edit-account.htm', 'volunteer-edit-account'],
    ['volunteer-change-password.htm', 'volunteer-change-password'],
  ],
};
// The account frame each design uses; the live pages must have all of it.
const FRAME = {
  user: ['inner-hero', 'acc-section', 'acc-sidebar', 'acc-user', 'acc-avatar', 'acc-nav', 'acc-nav-link', 'acc-mobile-nav', 'acc-mobile-toggle', 'acc-head', 'acc-title'],
  volunteer: ['inner-hero', 'vd-section', 'vd-layout', 'vd-side', 'vd-profile', 'vd-avatar', 'vd-role', 'vd-nav', 'vd-nav-link', 'vd-main'],
};

const mainClasses = (page) => page.$$eval('main *', (els) => [...new Set(els.flatMap((e) => [...e.classList]))]);

test.describe.serial('account design', () => {
  test.afterAll(() => {
    for (const [cid] of sql(`select customers_id from wl_customers where user_name like 'pw-test+ui-%@example.com'`)) {
      for (const [photo] of sql(`select coalesce(photo,'') from wl_volunteers where customers_id=${cid}`)) {
        const f = path.join(__dirname, '..', '..', '..', 'uploads', 'volunteers', photo);
        if (photo && fs.existsSync(f)) fs.unlinkSync(f);
      }
      sql(`delete from wl_volunteers where customers_id=${cid}`);
    }
    sql(`delete from wl_customers where user_name like 'pw-test+ui-%@example.com'`);
  });

  for (const role of ['user', 'volunteer']) {
    test(`${role} account pages match the reference`, async ({ browser, baseURL }) => {
      test.setTimeout(5 * 60_000);
      fs.mkdirSync(OUT, { recursive: true });
      const ctx = await browser.newContext({ baseURL, viewport: { width: 1440, height: 900 } });
      const page = await ctx.newPage();

      // Sign up through the real forms.
      if (role === 'user') {
        await page.goto('register');
        await page.fill('input[name="first_name"]', 'Alex');
        await page.fill('input[name="last_name"]', 'Tan');
        await page.fill('input[name="email"]', USER.email);
        await page.fill('input[name="mobile_number"]', `+65 9${String(Date.now()).slice(-7)}`);
        await page.fill('input[name="password"]', USER.pass);
        await page.fill('input[name="confirm_password"]', USER.pass);
        await page.fill('input[name="captcha_code"]', await captchaCode(page, 'register'));
        await page.check('input[name="terms"]');
        await page.locator('form[action$="register"] button[type="submit"]').click();
        await expect(page).toHaveURL(/\/dashboard$/);
      } else {
        await page.goto('volunteer-training-registration');
        const f = page.locator('form[action*="volunteer-training-registration"]');
        await f.locator('input[name="name"]').fill('Sample Volunteer');
        await f.locator('input[name="email"]').fill(VOL.email);
        await f.locator('input[name="mobile"]').fill(`+65 8${String(Date.now()).slice(-7)}`);
        await f.locator('select[name="age_group"]').selectOption('26-35');
        await f.locator('select[name="gender"]').selectOption('prefer-not-to-say');
        await f.locator('input[name="password"]').fill(VOL.pass);
        await f.locator('input[name="confirm_password"]').fill(VOL.pass);
        await f.locator('input[name="interests[]"][value="event-support"]').check();
        await f.locator('select[name="session"]').selectOption('next-available');
        await f.locator('select[name="experience"]').selectOption('none');
        await f.locator('input[name="availability[]"][value="weekends"]').check();
        await f.locator('[name="reason"]').fill('PW-TEST design comparison.');
        await f.locator('input[name="captcha_code"]').fill(await captchaCode(page, 'volunteer_register'));
        await f.locator('input[name="consent"]').check();
        await f.locator('button[type="submit"]').click();
        await expect(page).toHaveURL(/volunteer-dashboard/);
      }

      const report = {};
      for (const [refFile, route] of PAIRS[role]) {
        const ref = await ctx.newPage();
        await ref.goto(REF(refFile));
        const refClasses = await mainClasses(ref);
        await page.goto(route);
        const liveClasses = await mainClasses(page);
        const missing = refClasses.filter((c) => !liveClasses.includes(c));
        report[route] = { reference: refFile, missingFromLive: missing, extraInLive: liveClasses.filter((c) => !refClasses.includes(c)) };
        for (const c of FRAME[role]) expect.soft(liveClasses, `${route} uses .${c}`).toContain(c);

        for (const [label, size] of [['desktop', { width: 1440, height: 900 }], ['mobile', { width: 390, height: 844 }]]) {
          await ref.setViewportSize(size);
          await page.setViewportSize(size);
          await ref.screenshot({ path: path.join(OUT, `${route}-${label}-reference.png`), fullPage: true });
          await page.screenshot({ path: path.join(OUT, `${route}-${label}-live.png`), fullPage: true });
          const o = await horizontalOverflow(page);
          expect.soft(o.scrollWidth, `${route} ${label}: no sideways scroll (${o.offenders.join(', ')})`).toBeLessThanOrEqual(o.width + 1);
        }
        // Mobile: the account menu is reachable.
        if (role === 'user') {
          await expect.soft(page.locator('.acc-mobile-toggle')).toBeVisible();
          await page.locator('.acc-mobile-toggle').click();
          await expect.soft(page.locator('.acc-mobile-menu')).toBeVisible();
        } else {
          await expect.soft(page.locator('.vd-side .vd-nav')).toBeVisible();
        }
        await page.setViewportSize({ width: 1440, height: 900 });
        await ref.close();
      }
      fs.writeFileSync(path.join(OUT, `diff-${role}.json`), JSON.stringify(report, null, 1));
      await ctx.close();
    });
  }
});
