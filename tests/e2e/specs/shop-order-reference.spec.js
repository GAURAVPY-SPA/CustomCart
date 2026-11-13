// Order URLs carry ?order=<md5 of the invoice number>, never the invoice number:
// order confirmation, account order page and printable invoice; ownership is
// still checked (another session / customer gets 404) and old invoice-number
// links only redirect for the order's owner. Test orders and customers are removed.
const crypto = require('crypto');
const { test, expect } = require('@playwright/test');
const { watch, expectClean, captchaCode, unique, sql, esc } = require('../helpers');

const md5 = (s) => crypto.createHash('md5').update(s).digest('hex');
const REF_URL = /\/order-confirmation\?order=[a-f0-9]{32}$/;
const tag = unique();
const GUEST = `pw-test+ref-guest-${tag}@example.com`;
const USER = { email: `pw-test+ref-user-${tag}@example.com`, pass: 'RefPass123' };

async function addToCart(page) {
  await page.goto('basic-theory-study-planner');
  await page.locator('#productBuyForm button[name="buy_now"][value="0"]').click();
  await expect(page.locator('#cartCount')).not.toBeHidden();
}

// Checkout as the current visitor (guest or signed in); returns the order row.
async function placeOrder(page, email) {
  await addToCart(page);
  await page.goto('checkout');
  await page.fill('input[name="full_name"]', 'Pwtest Buyer');
  if (await page.locator('input[name="email"]').isEditable()) await page.fill('input[name="email"]', email);
  await page.fill('input[name="phone"]', '+65 91234567');
  await page.locator('form[action$="/checkout"] button[type="submit"]').click();
  await expect(page).toHaveURL(/delivery-information/);
  await page.fill('input[name="first_name"]', 'Pwtest');
  await page.fill('input[name="last_name"]', 'Buyer');
  await page.fill('input[name="phone"]', '+65 91234567');
  await page.fill('[name="address"]', '1 Test Street');
  await page.fill('input[name="zipcode"]', '310123');
  await page.locator('form[action$="/delivery-information"] button[type="submit"]').click();
  await expect(page).toHaveURL(/\/payment/);
  await page.locator('input[name="pay_method"][value="paynow"]').check({ force: true });
  await page.locator('input[name="terms"]').check({ force: true });
  await page.locator('form[action$="/payment"] button[type="submit"]').click();
  await page.waitForURL(/order-confirmation/);
  const [[id, invoice]] = sql(`select order_id, invoice_number from wl_order where email='${esc(email)}' order by order_id desc limit 1`);
  return { id, invoice };
}

const notFound = async (page, res) => {
  expect(res.status()).toBe(404);
  await expect(page.locator('.status-code')).toHaveText('404');
};

test.describe.serial('order references in URLs', () => {
  let guestOrder;
  let guestUrl;

  test.afterAll(() => {
    for (const [orderId] of sql(`select order_id from wl_order where email like 'pw-test+ref-%@example.com'`)) {
      for (const [stockId, qty] of sql(`select stock_id, quantity from wl_orders_products where orders_id=${orderId}`)) {
        sql(`update wl_product_stock set product_quantity=product_quantity+${qty}, used_quantity=greatest(used_quantity-${qty},0) where stock_id=${stockId}`);
      }
      sql(`delete from wl_orders_products where orders_id=${orderId}`);
      sql(`delete from wl_order where order_id=${orderId}`);
    }
    sql(`delete from wl_customers where user_name like 'pw-test+ref-%@example.com'`);
    sql(`delete from wl_newsletters where subscriber_email like 'pw-test+ref-%@example.com'`);
  });

  test('guest order: confirmation URL has the md5 reference, not the invoice number', async ({ page }) => {
    const log = watch(page);
    guestOrder = await placeOrder(page, GUEST);
    guestUrl = page.url();
    expect(guestUrl).toMatch(REF_URL);
    expect(guestUrl).not.toContain(guestOrder.invoice);
    expect(guestUrl).not.toMatch(new RegExp(`[?&=/]${guestOrder.id}(\\b|$)`));
    expect(new URL(guestUrl).searchParams.get('order')).toBe(md5(guestOrder.invoice));
    // The invoice number is still shown on the page.
    await expect(page.locator('#invoiceTitle')).toHaveText(guestOrder.invoice);
    // A guest gets a Print button, not the account's printable-invoice link.
    await expect(page.locator('a[href*="invoice-print"]')).toHaveCount(0);
    // Refresh keeps working.
    await page.reload();
    await expect(page.locator('#invoiceTitle')).toHaveText(guestOrder.invoice);
    expectClean(log);

    // Old invoice-number link from the same session: sent to the reference address.
    await page.goto(`order-confirmation?order=${guestOrder.invoice}`);
    await expect(page).toHaveURL(guestUrl);
  });

  test('without the session, the reference and the old link show nothing', async ({ browser, baseURL }) => {
    const ctx = await browser.newContext({ baseURL });
    const page = await ctx.newPage();
    await notFound(page, await page.goto(guestUrl));
    await expect(page.locator('main')).not.toContainText(guestOrder.invoice);
    await notFound(page, await page.goto(`order-confirmation?order=${guestOrder.invoice}`));
    await expect(page.locator('main')).not.toContainText(guestOrder.invoice);
    // Invalid references.
    for (const bad of ['0123456789abcdef0123456789abcdef', md5('YSD99999'), 'not-a-ref', '', `${md5(guestOrder.invoice)}x`]) {
      await notFound(page, await page.goto(`order-confirmation?order=${encodeURIComponent(bad)}`));
    }
    await ctx.close();
  });

  test('signed-in customer: account order links use references; others cannot open them', async ({ browser, baseURL }) => {
    const ctx = await browser.newContext({ baseURL });
    const page = await ctx.newPage();
    const log = watch(page);
    await page.goto('register');
    await page.fill('input[name="first_name"]', 'Pwtest');
    await page.fill('input[name="last_name"]', 'Owner');
    await page.fill('input[name="email"]', USER.email);
    await page.fill('input[name="mobile_number"]', `+65 9${String(Date.now()).slice(-7)}`);
    await page.fill('input[name="password"]', USER.pass);
    await page.fill('input[name="confirm_password"]', USER.pass);
    await page.fill('input[name="captcha_code"]', await captchaCode(page, 'register'));
    await page.check('input[name="terms"]');
    await page.locator('form[action$="register"] button[type="submit"]').click();
    await expect(page).toHaveURL(/\/dashboard$/);

    const order = await placeOrder(page, USER.email);
    expect(page.url()).toMatch(REF_URL);
    const ref = md5(order.invoice);
    expect(page.url()).toContain(`?order=${ref}`);
    // The owner's Print Invoice link carries the reference too.
    expect(await page.locator('a[href*="invoice-print"]').getAttribute('href')).toContain(`invoice-print?order=${ref}`);

    // Order history and dashboard links: references only.
    for (const list of ['acc-order-history', 'dashboard']) {
      await page.goto(list);
      const hrefs = await page.$$eval('a[href*="acc-order-invoice"]', (as) => as.map((a) => a.getAttribute('href')));
      expect(hrefs.length, `${list} order links`).toBeGreaterThan(0);
      for (const h of hrefs) expect(h).toMatch(/acc-order-invoice\?order=[a-f0-9]{32}$/);
      expect(hrefs).toContain(`${new URL('acc-order-invoice', baseURL).href}?order=${ref}`);
    }

    // Order page: invoice shown, links and the cancel/return form use the reference.
    await page.goto(`acc-order-invoice?order=${ref}`);
    await expect(page.locator('main')).toContainText(order.invoice);
    const html = await page.content();
    expect(html).not.toMatch(new RegExp(`\\?order=${order.invoice}`));
    expect(await page.locator('a[href*="invoice-print"]').first().getAttribute('href')).toMatch(/invoice-print\?order=[a-f0-9]{32}$/);
    await page.goto(`invoice-print?order=${ref}`);
    await expect(page.locator('body')).toContainText(order.invoice);
    expect(await page.locator('a[href*="acc-order-invoice"]').first().getAttribute('href')).toContain(`?order=${ref}`);

    // Old invoice-number links (e.g. earlier order emails): redirected for the owner.
    await page.goto(`acc-order-invoice?order=${order.invoice}`);
    await expect(page).toHaveURL(new RegExp(`acc-order-invoice\\?order=${ref}$`));
    await page.goto(`invoice-print?order=${order.invoice}`);
    await expect(page).toHaveURL(new RegExp(`invoice-print\\?order=${ref}$`));

    // The guest's order is not this customer's: 404 by reference and by invoice number.
    const gref = md5(guestOrder.invoice);
    for (const url of [`order-confirmation?order=${gref}`, `acc-order-invoice?order=${gref}`, `invoice-print?order=${gref}`,
      `acc-order-invoice?order=${guestOrder.invoice}`, `order-confirmation?order=${guestOrder.invoice}`]) {
      const res = await page.goto(url);
      expect(res.status(), url).toBe(404);
      await expect(page.locator('body'), url).not.toContainText(guestOrder.invoice);
    }
    expectClean(log, { allowStatus: ['order-confirmation', 'acc-order-invoice', 'invoice-print'] });
    await ctx.close();

    // A visitor without this account cannot open the customer's order by its reference.
    const other = await browser.newContext({ baseURL });
    const op = await other.newPage();
    await notFound(op, await op.goto(`order-confirmation?order=${ref}`));
    await op.goto(`acc-order-invoice?order=${ref}`);
    await expect(op).toHaveURL(/\/login/);
    await other.close();
  });
});
