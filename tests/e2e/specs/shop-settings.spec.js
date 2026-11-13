// Online store amounts of Sitepanel > Settings (shipping_charges, free_delivery_above,
// gst_percent): the Settings form and its validation, the cart / checkout / order
// calculations, GST of placed orders and that wl_shipping no longer matters.
// Changes store-wide settings: runs after the other projects and restores them.
const { test, expect } = require('@playwright/test');
const { watch, expectClean, unique, sql, esc } = require('../helpers');

const ADMIN_URL = process.env.ADMIN_URL || 'http://localhost:8081/';
const KEYS = ['shipping_charges', 'free_delivery_above', 'gst_percent'];
const PRODUCT = { url: 'basic-theory-study-planner', price: 10, stockId: 59 }; // S$10.00: qty x 10 = subtotal
const money = (n) => `S$${n.toFixed(2)}`;
const gstOf = (total, rate) => Math.round((total * rate / (100 + rate)) * 100) / 100;

let original = [];
function setAmounts(shipping, free, gst) {
  for (const [key, value] of [['shipping_charges', shipping], ['free_delivery_above', free], ['gst_percent', gst]]) {
    sql(`insert into tbl_site_settings (setting_key, setting_value, updated_at) values ('${key}', '${value.toFixed(2)}', now())
         on duplicate key update setting_value=values(setting_value), updated_at=now()`);
  }
}

async function adminPage(browser) {
  const ctx = await browser.newContext({ baseURL: ADMIN_URL });
  const page = await ctx.newPage();
  await page.goto('sitepanel/login');
  await page.click('#username');
  await expect(page.locator('#username')).toBeEditable();
  await page.fill('#username', process.env.SP_USER);
  await page.fill('#password', process.env.SP_PASS);
  await page.click('button[type="submit"]');
  await expect(page).toHaveURL(/sitepanel\/dashboard/);
  return { ctx, page };
}

// A fresh cart holding qty x the S$10 product; returns the cart page's figures.
async function cartWith(browser, baseURL, qty) {
  const ctx = await browser.newContext({ baseURL });
  const page = await ctx.newPage();
  await page.goto(PRODUCT.url);
  await page.fill('#productQty', String(qty));
  await page.locator('#productBuyForm button[name="buy_now"][value="0"]').click();
  await expect(page.locator('#cartCount')).toHaveText(String(qty));
  await page.goto('cart');
  const hint = page.locator('#cartHint');
  const figures = {
    delivery: (await page.locator('#cartDelivery').innerText()).trim(),
    total: (await page.locator('#cartTotal').innerText()).trim(),
    hint: (await hint.count()) ? (await hint.innerText()).trim() : null,
    note: (await page.locator('.summary-note').innerText()).trim(),
  };
  return { ctx, page, figures };
}

test.describe.serial('online store settings', () => {
  test.beforeAll(() => {
    original = sql(`select setting_key, setting_value from tbl_site_settings where setting_key in ('${KEYS.join("','")}')`);
  });

  test.afterAll(() => {
    // Settings back as they were (rows that did not exist are removed: defaults apply).
    sql(`delete from tbl_site_settings where setting_key in ('${KEYS.join("','")}')`);
    for (const [key, value] of original) sql(`insert into tbl_site_settings (setting_key, setting_value, updated_at) values ('${key}', '${esc(value)}', now())`);
    // Test orders: stock back, then lines, orders and guest customers removed.
    for (const [orderId] of sql(`select order_id from wl_order where email like 'pw-test+shop-%@example.com'`)) {
      for (const [stockId, qty] of sql(`select stock_id, quantity from wl_orders_products where orders_id=${orderId}`)) {
        sql(`update wl_product_stock set product_quantity=product_quantity+${qty}, used_quantity=greatest(used_quantity-${qty},0) where stock_id=${stockId}`);
      }
      sql(`delete from wl_orders_products where orders_id=${orderId}`);
      sql(`delete from wl_order where order_id=${orderId}`);
    }
    sql(`delete from wl_customers where user_name like 'pw-test+shop-%@example.com'`);
    sql(`delete from wl_newsletters where subscriber_email like 'pw-test+shop-%@example.com'`);
  });

  test('Sitepanel Settings: fields, save, persist and validation', async ({ browser }) => {
    const { ctx, page } = await adminPage(browser);
    const log = watch(page);
    sql(`delete from tbl_site_settings where setting_key in ('${KEYS.join("','")}')`);
    await page.goto('sitepanel/settings');
    // Defaults shown when nothing is saved yet.
    await expect(page.locator('#shipping_charges')).toHaveValue('6.00');
    await expect(page.locator('#free_delivery_above')).toHaveValue('150.00');
    await expect(page.locator('#gst_percent')).toHaveValue('9.00');

    await page.fill('#shipping_charges', '8.5');
    await page.fill('#free_delivery_above', '120');
    await page.fill('#gst_percent', '7');
    await page.locator('.sp-form-actions button[type="submit"]').click();
    await expect(page.locator('.alert-success').first()).toBeVisible();
    await page.reload();
    await expect(page.locator('#shipping_charges')).toHaveValue('8.50');
    await expect(page.locator('#free_delivery_above')).toHaveValue('120.00');
    await expect(page.locator('#gst_percent')).toHaveValue('7.00');
    expect(sql(`select setting_value from tbl_site_settings where setting_key in ('shipping_charges','free_delivery_above','gst_percent') order by setting_key`).flat())
      .toEqual(['120.00', '7.00', '8.50']);

    // Invalid values are refused and nothing changes.
    for (const [field, value, message] of [
      ['#shipping_charges', '-1', /Shipping Charges/],
      ['#free_delivery_above', '-5', /Free Delivery Above/],
      ['#gst_percent', '-2', /GST Percentage/],
      ['#gst_percent', '100.01', /GST Percentage.*not be more than 100/],
      ['#shipping_charges', 'abc', /Shipping Charges/],
      ['#shipping_charges', '', /Shipping Charges/],
    ]) {
      await page.goto('sitepanel/settings');
      await page.fill(field, value);
      await page.locator('.sp-form-actions button[type="submit"]').click();
      await expect(page.locator('.alert-danger'), `${field}=${value}`).toContainText(message);
    }
    expect(sql(`select setting_value from tbl_site_settings where setting_key='gst_percent'`)[0][0]).toBe('7.00');

    // Boundaries accepted: 0 and GST 100.
    await page.goto('sitepanel/settings');
    await page.fill('#shipping_charges', '0');
    await page.fill('#free_delivery_above', '0.00');
    await page.fill('#gst_percent', '100');
    await page.locator('.sp-form-actions button[type="submit"]').click();
    await expect(page.locator('.alert-success').first()).toBeVisible();
    await expect(page.locator('#gst_percent')).toHaveValue('100.00');
    expectClean(log);
    await ctx.close();
  });

  // [shipping charge, free delivery above, quantity of the S$10 product, expected delivery]
  const CASES = [
    [6, 150, 10, 6], [6, 150, 14, 6], [6, 150, 15, 0], [6, 150, 20, 0],
    [10, 200, 10, 10], [10, 200, 19, 10], [10, 200, 20, 0], [10, 200, 25, 0],
    [10, 0, 10, 10], [10, 0, 30, 10],
    [0, 150, 10, 0],
  ];
  for (const [charge, free, qty, expected] of CASES) {
    test(`cart: charge ${charge}, free above ${free}, subtotal ${qty * PRODUCT.price} -> delivery ${expected}`, async ({ browser, baseURL }) => {
      setAmounts(charge, free, 9);
      const { ctx, page, figures } = await cartWith(browser, baseURL, qty);
      const log = watch(page);
      const subtotal = qty * PRODUCT.price;
      expect(figures.delivery).toBe(expected > 0 ? money(expected) : 'Free');
      expect(figures.total).toBe(money(subtotal + expected));
      if (expected > 0 && free > 0) expect(figures.hint).toBe(`Add ${money(free - subtotal)} more for free delivery.`);
      else expect(figures.hint, 'no free-delivery hint').toBeNull();
      expect(figures.note).toContain(charge > 0 ? `Island-wide delivery ${money(charge)}` : 'Free island-wide delivery.');
      if (charge > 0) expect(figures.note.includes('free on orders of')).toBe(free > 0);
      // The checkout summary uses the same figures.
      await page.goto('checkout');
      await expect(page.locator('.order-summary .summary-row', { hasText: 'Delivery' }).locator('dd')).toHaveText(expected > 0 ? money(expected) : 'Free');
      await expect(page.locator('.order-summary .summary-row-total dd')).toHaveText(money(subtotal + expected));
      expectClean(log);
      await ctx.close();
    });
  }

  for (const rate of [9, 7, 0, 100]) {
    test(`GST ${rate}%: cart and checkout`, async ({ browser, baseURL }) => {
      setAmounts(6, 150, rate);
      const { ctx, page, figures } = await cartWith(browser, baseURL, 10); // 100 + 6 delivery
      const gst = gstOf(106, rate);
      if (rate > 0) expect(figures.note).toContain(`Prices include GST (${rate}%): ${money(gst)}.`);
      else expect(figures.note).not.toContain('GST');
      await page.goto('checkout');
      const note = page.locator('.order-summary .summary-note');
      if (rate > 0) await expect(note).toContainText(`GST (${rate}%): ${money(gst)}`);
      else await expect(note).not.toContainText('GST');
      await ctx.close();
    });
  }

  test('order: saved delivery, GST and total follow Settings; GST of the order is kept', async ({ browser, baseURL }) => {
    setAmounts(8.5, 200, 7);
    const email = `pw-test+shop-${unique()}@example.com`;
    const { ctx, page } = await cartWith(browser, baseURL, 10); // subtotal 100
    const log = watch(page);

    await page.goto('checkout');
    await page.fill('input[name="full_name"]', 'Pwtest Shopper');
    await page.fill('input[name="email"]', email);
    await page.fill('input[name="phone"]', '+65 91234567');
    await page.locator('form button[type="submit"]').last().click();
    await expect(page).toHaveURL(/delivery-information/);
    // No delivery method to choose any more.
    await expect(page.locator('input[name="shipping_id"]')).toHaveCount(0);
    await page.fill('input[name="first_name"]', 'Pwtest');
    await page.fill('input[name="last_name"]', 'Shopper');
    await page.fill('input[name="phone"]', '+65 91234567');
    await page.fill('[name="address"]', '1 Test Street');
    if (await page.locator('select[name="country"]').count()) await page.selectOption('select[name="country"]', 'SG');
    await page.fill('input[name="zipcode"]', '310123');
    await page.locator('form button[type="submit"]').last().click();
    await expect(page).toHaveURL(/\/payment/);

    const total = 108.5;
    const gst = gstOf(total, 7); // 7.10
    await expect(page.locator('.order-summary .summary-row-total dd')).toHaveText(money(total));
    await page.locator('input[name="pay_method"][value="paynow"]').check({ force: true });
    await page.locator('input[name="terms"]').check({ force: true });
    await page.locator('form button[type="submit"]').last().click();
    await expect(page).toHaveURL(/order-confirmation/);

    const [[orderId, shipping, rate, vat, grand, method, shippingId]] = sql(
      `select order_id, shipping_amount, vat_applied_cent, vat_amount, grand_total, shipping_method, coalesce(shipping_id,'NULL') from wl_order where email='${esc(email)}'`);
    expect([shipping, rate, vat, grand]).toEqual(['8.50', '7.00', gst.toFixed(2), '108.50']);
    expect(method).toBe('Standard Delivery');
    expect(shippingId, 'no wl_shipping method recorded').toBe('NULL');
    await expect(page.locator('main')).toContainText('GST (7%, included)');
    await expect(page.locator('main')).toContainText(money(gst));
    await expect(page.locator('main')).toContainText(money(8.5));

    // New GST rate: the placed order keeps 7% everywhere.
    setAmounts(8.5, 200, 9);
    await page.reload();
    await expect(page.locator('main')).toContainText('GST (7%, included)');
    const { ctx: actx, page: admin } = await adminPage(browser);
    await admin.goto(`sitepanel/orders/view/${orderId}`);
    await expect(admin.locator('body')).toContainText('Includes GST (7%)');
    await expect(admin.locator('body')).toContainText(money(gst));
    await expect(admin.locator('body')).toContainText(money(8.5));
    await admin.goto(`sitepanel/orders/invoice/${orderId}`);
    await expect(admin.locator('body')).toContainText('Includes GST (7%)');
    expectClean(log);
    await actx.close();
    await ctx.close();
  });

  test('older orders keep their GST rate after the setting changes', async ({ browser }) => {
    const [[orderId, rate, vat]] = sql(`select order_id, vat_applied_cent, vat_amount from wl_order where vat_amount > 0 and email not like 'pw-test+%' order by order_id limit 1`);
    setAmounts(6, 150, 3);
    const { ctx, page } = await adminPage(browser);
    await page.goto(`sitepanel/orders/view/${orderId}`);
    const label = `Includes GST (${String(Number(rate))}%)`;
    await expect(page.locator('body')).toContainText(label);
    await expect(page.locator('body')).toContainText(money(Number(vat)));
    await expect(page.locator('body')).not.toContainText('Includes GST (3%)');
    expect(sql(`select vat_applied_cent, vat_amount from wl_order where order_id=${orderId}`)[0]).toEqual([rate, vat]);
    await ctx.close();
  });
});
