// User account (online store customer): registration, login/logout, every
// My Account page and its navigation, edit account, change password,
// cart to checkout, forgot/reset password, role restrictions, and that the
// wishlist is hidden everywhere (no wishlist in this project).
// The test account (pw-test+user-*@example.com) is removed at the end.
const { test, expect } = require('@playwright/test');
const { watch, expectClean, expectSaneHtml, captchaCode, unique, sql, esc } = require('../helpers');

const id = unique();
const EMAIL = `pw-test+user-${id}@example.com`;
const PASS = 'TestPass123';
const NEW_PASS = 'NewPass456';
const MOBILE = `+65 9${String(Date.now()).slice(-7)}`;

const ACCOUNT_PAGES = [
  ['dashboard', 'Dashboard'],
  ['acc-order-history', 'Order History'],
  ['acc-event-booking-history', 'Event Booking History'],
  ['acc-edit', 'Edit Account'],
  ['change-password', 'Change Password'],
];

async function login(page, email, password, type = 'user') {
  await page.goto(type === 'volunteer' ? 'login?type=volunteer' : 'login');
  const form = page.locator(`form:has(input[name="account_type"][value="${type}"])`);
  await form.locator('input[name="email"]').fill(email);
  await form.locator('input[name="password"]').fill(password);
  await form.locator('button[type="submit"]').click();
}

test.describe.serial('user account', () => {
  test.afterAll(() => {
    const rows = sql(`select customers_id from wl_customers where user_name like 'pw-test+user-%@example.com'`);
    for (const [cid] of rows) {
      sql(`delete from wl_wishlists where customer_id=${cid}`);
      sql(`delete from wl_customers_address_book where customer_id=${cid}`);
    }
    sql(`delete from wl_newsletters where subscriber_email like 'pw-test+user-%@example.com'`);
    sql(`delete from wl_customers where user_name like 'pw-test+user-%@example.com'`);
  });

  test('account pages need a login', async ({ page }) => {
    for (const [url] of ACCOUNT_PAGES) {
      await page.goto(url);
      await expect(page, url).toHaveURL(/\/login/);
    }
  });

  test('registration validates required fields', async ({ page }) => {
    const log = watch(page);
    await page.goto('register');
    await expectSaneHtml(page);
    await page.evaluate(() => document.querySelectorAll('form [required]').forEach((e) => e.removeAttribute('required')));
    await page.locator('form[action$="register"] button[type="submit"]').click();
    const alert = page.locator('.alert-danger, .invalid-feedback.d-block').first();
    await expect(alert).toBeVisible();
    await expect(page.locator('body')).toContainText(/First Name/i);
    await expect(page.locator('body')).toContainText(/verification code|Terms of Use/i);

    // Mismatched passwords + wrong code.
    await page.fill('input[name="first_name"]', 'Pw');
    await page.fill('input[name="email"]', 'not-an-email');
    await page.fill('input[name="password"]', PASS);
    await page.fill('input[name="confirm_password"]', 'Different1');
    await page.locator('form[action$="register"] button[type="submit"]').click();
    await expect(page.locator('body')).toContainText(/valid email/i);
    await expect(page.locator('body')).toContainText(/do not match/i);
    expectClean(log);
  });

  test('register, then land on My Account', async ({ page }) => {
    const log = watch(page);
    await page.goto('register');
    await page.fill('input[name="first_name"]', 'Pwtest');
    await page.fill('input[name="last_name"]', 'User');
    await page.fill('input[name="email"]', EMAIL);
    await page.fill('input[name="mobile_number"]', MOBILE);
    await page.fill('input[name="password"]', PASS);
    await page.fill('input[name="confirm_password"]', PASS);
    // "Show a new code" loads a new image (and code) without reloading the page; the new code is the one accepted.
    await page.locator('input[name="captcha_code"]').fill('XXXXX');
    await Promise.all([page.waitForResponse(/captcha\/register\?r=/), page.click('#regCaptchaRefresh')]);
    await expect(page.locator('#regCaptchaImage')).toHaveAttribute('src', /captcha\/register\?r=\d+/);
    await expect(page.locator('input[name="captcha_code"]')).toHaveValue('');
    await expect(page.locator('input[name="email"]')).toHaveValue(EMAIL); // other fields kept
    await page.fill('input[name="captcha_code"]', await captchaCode(page, 'register'));
    await page.check('input[name="terms"]');
    await page.locator('form[action$="register"] button[type="submit"]').click();
    await expect(page).toHaveURL(/\/dashboard$/);
    await expect(page.locator('.acc-title')).toContainText('Welcome Back, Pwtest');
    expect(sql(`select account_type, status from wl_customers where user_name='${esc(EMAIL)}'`)).toEqual([['user', '1']]);
    expectClean(log);
  });

  test('logout from the modal, login again', async ({ page }) => {
    await login(page, EMAIL, PASS);
    await expect(page).toHaveURL(/\/dashboard$/);
    await page.locator('.acc-sidebar .acc-logout').click();
    await expect(page.locator('#accLogoutModal')).toBeVisible();
    await page.locator('#accLogoutModal button[type="submit"]').click();
    await expect(page).toHaveURL(/\/login$/);
    await page.goto('dashboard');
    await expect(page).toHaveURL(/\/login/);

    // Wrong password.
    await login(page, EMAIL, 'WrongPass999');
    await expect(page.locator('.alert-danger, [role="alert"]').first()).toBeVisible();
    await expect(page).toHaveURL(/\/login/);
  });

  test('every My Account page and its navigation', async ({ page }) => {
    const log = watch(page);
    await login(page, EMAIL, PASS);
    for (const [url, label] of ACCOUNT_PAGES) {
      const res = await page.goto(url);
      expect.soft(res.status(), url).toBe(200);
      await expectSaneHtml(page);
      await expect.soft(page.locator('.acc-sidebar .acc-nav-link.is-active'), `${url} active item`).toHaveText(label);
      // Design frame of html/dashboard.htm.
      await expect.soft(page.locator('.inner-hero .inner-hero-title')).toBeVisible();
      await expect.soft(page.locator('.acc-section .acc-sidebar .acc-user .acc-avatar')).toHaveText('PU');
      // Volunteer-only items never shown to a user.
      await expect.soft(page.locator('.acc-nav')).not.toContainText(/Course Booking|Volunteer|Wishlist/);
    }
    // The sidebar links open their pages.
    await page.goto('dashboard');
    for (const [url, label] of ACCOUNT_PAGES.slice(1)) {
      await page.locator('.acc-sidebar .acc-nav-link', { hasText: label }).click();
      await expect(page).toHaveURL(new RegExp(`/${url}$`));
    }
    // Dashboard cards.
    await page.goto('dashboard');
    await expect(page.locator('.acc-stat')).toHaveCount(3); // Orders, Account Details, Password (Wishlist hidden)
    expectClean(log);
  });

  test('edit account saves to the database', async ({ page }) => {
    const log = watch(page);
    await login(page, EMAIL, PASS);
    await page.goto('acc-edit');
    await page.fill('input[name="first_name"]', '');
    await page.evaluate(() => document.querySelectorAll('form [required]').forEach((e) => e.removeAttribute('required')));
    await page.locator('#accEditForm button[type="submit"]').click();
    await expect(page.locator('.alert-danger').first()).toBeVisible();

    await page.fill('input[name="first_name"]', 'Pwtest');
    await page.fill('input[name="city"]', 'Singapore');
    if (await page.locator('[name="address"]').count()) await page.fill('[name="address"]', '1 Test Street');
    if (await page.locator('[name="zipcode"]').count()) await page.fill('[name="zipcode"]', '123456');
    await page.locator('#accEditForm button[type="submit"]').click();
    await expect(page.locator('.alert-success, .shop-flash-success, [role="status"]').first()).toBeVisible();
    expect(sql(`select city from wl_customers where user_name='${esc(EMAIL)}'`)[0][0]).toBe('Singapore');
    expectClean(log);
  });

  test('change password: wrong current refused, then changed', async ({ page }) => {
    const log = watch(page);
    await login(page, EMAIL, PASS);
    await page.goto('change-password');
    await page.fill('input[name="current_password"]', 'WrongPass999');
    await page.fill('input[name="new_password"]', NEW_PASS);
    await page.fill('input[name="confirm_password"]', NEW_PASS);
    await page.locator('#accPasswordForm button[type="submit"]').click();
    await expect(page.locator('.alert-danger').first()).toBeVisible();

    await page.fill('input[name="current_password"]', PASS);
    await page.fill('input[name="new_password"]', NEW_PASS);
    await page.fill('input[name="confirm_password"]', NEW_PASS);
    await page.locator('#accPasswordForm button[type="submit"]').click();
    await expect(page.locator('.alert-danger')).toHaveCount(0);
    await page.context().clearCookies();
    await login(page, EMAIL, NEW_PASS);
    await expect(page).toHaveURL(/\/dashboard$/);
    expectClean(log);
  });

  test('the wishlist is hidden everywhere', async ({ page }) => {
    const log = watch(page);
    await login(page, EMAIL, NEW_PASS);
    for (const url of ['basic-theory-study-planner', 'dashboard', 'acc-order-history', 'acc-edit', 'change-password', '', 'sitemap', 'cart']) {
      await page.goto(url);
      await expect(page.locator('.js-wishlist-btn, a[href*="acc-wishlist"]'), `wishlist on /${url}`).toHaveCount(0);
      expect((await page.locator('body').innerText()).toLowerCase(), `wishlist text on /${url}`).not.toContain('wishlist');
    }
    const res = await page.goto('acc-wishlist');
    expect(res.status()).toBe(404);
    expectClean(log, { allowStatus: ['acc-wishlist'] });
  });

  test('cart and checkout pages', async ({ page }) => {
    const log = watch(page);
    await login(page, EMAIL, NEW_PASS);
    await page.goto('basic-theory-study-planner');
    await page.fill('#productQty', '2');
    await page.locator('#productBuyForm button[name="buy_now"][value="0"]').click();
    await page.waitForLoadState('networkidle');
    await page.goto('cart');
    await expect(page.locator('main')).toContainText('Basic Theory Study Planner');
    await expectSaneHtml(page);
    for (const url of ['checkout', 'delivery-information']) {
      const res = await page.goto(url);
      expect.soft(res.status(), url).toBe(200);
      await expectSaneHtml(page);
    }
    expectClean(log);
  });

  test('a user cannot open volunteer pages', async ({ page }) => {
    await login(page, EMAIL, NEW_PASS);
    for (const url of ['volunteer-dashboard', 'volunteer-edit-account', 'volunteer-course-booking', 'volunteer-change-password']) {
      await page.goto(url);
      await expect(page, url).not.toHaveURL(new RegExp(`/${url}$`));
    }
    // A user account cannot sign in on the volunteer tab.
    await page.context().clearCookies();
    await login(page, EMAIL, NEW_PASS, 'volunteer');
    await expect(page).not.toHaveURL(/volunteer-dashboard/);
  });

  test('forgot password and reset password', async ({ page }) => {
    const log = watch(page);
    await page.goto('forgot-password');
    await page.fill('form[action$="forgot-password"] input[name="email"]', EMAIL);
    await page.locator('form[action$="forgot-password"] button[type="submit"]').click();
    await expect(page.locator('.alert-success, [role="status"], .alert-info').first()).toBeVisible();
    const [[token]] = sql(`select coalesce(reset_token,'') from wl_customers where user_name='${esc(EMAIL)}'`);
    expect(token, 'reset token saved').not.toBe('');

    // Invalid token: refused.
    await page.goto('reset-password?token=invalid-token-123');
    await expectSaneHtml(page);
    await expect(page.locator('main')).toContainText(/invalid|expired|no longer/i);
    expectClean(log);
  });
});
