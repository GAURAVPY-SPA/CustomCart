// Public forms: required fields, invalid input and a real submission that is
// checked in the database. Test records use pw-test+*@example.com and are removed.
const { test, expect } = require('@playwright/test');
const { watch, expectClean, captchaCode, unique, sql } = require('../helpers');

const tag = unique();
const email = (what) => `pw-test+${what}-${tag}@example.com`;
const stripRequired = (page) => page.evaluate(() => document.querySelectorAll('form [required]').forEach((e) => e.removeAttribute('required')));

test.afterAll(() => {
  sql(`delete from wl_enquiry where email like 'pw-test+%@example.com'`);
  sql(`delete from wl_newsletters where subscriber_email like 'pw-test+%@example.com'`);
  sql(`delete from wl_testimonial where email like 'pw-test+%@example.com'`);
});

test('contact form: validation, then a saved enquiry', async ({ page }) => {
  const log = watch(page);
  await page.goto('contactus');
  const form = page.locator('form[action$="contactus"]');
  await stripRequired(page);
  await form.locator('button[type="submit"]').click();
  await expect(page.locator('.alert-danger, .invalid-feedback.d-block').first()).toBeVisible();

  await page.goto('contactus');
  await form.locator('input[name="name"]').fill('Pwtest Contact');
  await form.locator('input[name="email"]').fill(email('contact'));
  await form.locator('input[name="phone"]').fill('+65 91234567');
  if (await form.locator('[name="subject"]').count()) {
    const subject = form.locator('[name="subject"]');
    if (await subject.evaluate((e) => e.tagName === 'SELECT')) await subject.selectOption({ index: 1 }); else await subject.fill('PW-TEST subject');
  }
  await form.locator('[name="message"]').fill('PW-TEST message from the automated test.');
  await form.locator('input[name="captcha_code"]').fill(await captchaCode(page, 'contact'));
  await form.locator('button[type="submit"]').click();
  await expect(page.locator('.inner-hero-title')).toHaveText('Thank You'); // html/thanks.htm
  await expect(page.locator('#statusText')).toContainText(/sent successfully/i);
  expect(sql(`select count(*) from wl_enquiry where email='${email('contact')}'`)[0][0]).toBe('1');
  expectClean(log);
});

test('contact form: a wrong verification code is refused', async ({ page }) => {
  await page.goto('contactus');
  const form = page.locator('form[action$="contactus"]');
  await form.locator('input[name="name"]').fill('Pwtest Contact');
  await form.locator('input[name="email"]').fill(email('contact2'));
  await form.locator('input[name="phone"]').fill('+65 91234567');
  await form.locator('[name="message"]').fill('PW-TEST message.');
  await form.locator('input[name="captcha_code"]').fill('ZZZZZ');
  await form.locator('button[type="submit"]').click();
  await expect(page.locator('body')).toContainText(/verification code/i);
  expect(sql(`select count(*) from wl_enquiry where email='${email('contact2')}'`)[0][0]).toBe('0');
});

test('newsletter: invalid email in red, subscription in green', async ({ page }) => {
  const log = watch(page);
  await page.goto('#newsletter');
  const form = page.locator('#newsletterForm');
  await form.locator('input[name="email"]').fill('not-an-email');
  await form.locator('button[type="submit"]').click();
  await expect(page.locator('#newsletterStatus.newsletter-status-error')).toBeVisible();

  await form.locator('input[name="email"]').fill(email('news'));
  await form.locator('button[type="submit"]').click();
  const ok = page.locator('#newsletterStatus.newsletter-status-success');
  await expect(ok).toBeVisible();
  await expect(ok).toContainText(/subscribed/i);
  const bg = await ok.evaluate((e) => getComputedStyle(e).backgroundColor);
  expect(bg, 'success background').toBe('rgb(232, 246, 238)');
  expect(sql(`select count(*) from wl_newsletters where subscriber_email='${email('news')}'`)[0][0]).toBe('1');

  // Same address again: still a friendly message, no duplicate.
  await page.locator('#newsletterForm input[name="email"]').fill(email('news'));
  await page.locator('#newsletterForm button[type="submit"]').click();
  await expect(page.locator('#newsletterStatus')).toContainText(/already/i);
  expect(sql(`select count(*) from wl_newsletters where subscriber_email='${email('news')}'`)[0][0]).toBe('1');
  expectClean(log);
});

test('testimonial: validation, then saved for review', async ({ page }) => {
  const log = watch(page);
  await page.goto('testimonials');
  const form = page.locator('form[action*="testimonials"]').last();
  await stripRequired(page);
  await form.locator('button[type="submit"]').click();
  await expect(page.locator('.alert-danger, .invalid-feedback.d-block').first()).toBeVisible();

  await page.goto('testimonials');
  await form.locator('input[name="name"]').fill('Pwtest Reviewer');
  await form.locator('input[name="email"]').fill(email('testimonial'));
  await form.locator('[name="message"]').fill('PW-TEST testimonial text written by the automated test suite, long enough to pass.');
  await form.locator('button[type="submit"]').click();
  await expect(page.locator('.alert-success, [role="status"]').first()).toBeVisible();
  const rows = sql(`select status from wl_testimonial where email='${email('testimonial')}'`);
  expect(rows.length).toBe(1);
  expect(rows[0][0], 'new testimonials wait for review').not.toBe('1');
  expectClean(log);
});

test('product enquiry form is validated', async ({ page }) => {
  const log = watch(page);
  await page.goto('basic-theory-study-planner');
  const form = page.locator('#inquiryForm');
  await stripRequired(page);
  await form.locator('button[type="submit"]').click({ force: true });
  await expect(page.locator('.alert-danger, .invalid-feedback.d-block').first()).toBeVisible();
  expectClean(log);
});

test('donation form is validated (no payment is made)', async ({ page }) => {
  const log = watch(page);
  await page.goto('donations');
  await stripRequired(page);
  await page.locator('button[type="submit"][form="donationForm"]').click();
  await expect(page.locator('.alert-danger, .invalid-feedback.d-block, .is-invalid').first()).toBeVisible();
  expectClean(log);
});

test('courses: a guest is sent to log in before registering', async ({ page }) => {
  const log = watch(page);
  await page.goto('learning-courses');
  await expect(page.locator('.js-course-register')).toHaveCount(0);
  await page.locator('.course-btn', { hasText: 'Notify Me' }).first().click();
  await expect(page).toHaveURL(/\/login/);
  expectClean(log);
});
