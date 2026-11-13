// Signs in to the Sitepanel once and saves the session for the sitepanel specs.
// Needs SP_USER and SP_PASS (a super admin; see tests/e2e/README.md).
const { test: setup, expect } = require('@playwright/test');

const USER = process.env.SP_USER;
const PASS = process.env.SP_PASS;

setup('sitepanel login', async ({ page }) => {
  if (!USER || !PASS) throw new Error('Set SP_USER and SP_PASS to a Sitepanel super admin login to run the Sitepanel tests.');
  await page.goto('sitepanel/login');
  // The username field is readonly until focused (anti-autofill in admin_login.php).
  await page.click('#username');
  await expect(page.locator('#username')).toBeEditable();
  await page.fill('#username', USER);
  await page.fill('#password', PASS);
  await Promise.all([page.waitForURL(/sitepanel\/(dashboard|$)/), page.click('button[type="submit"]')]);
  await expect(page.locator('body')).toContainText(/Dashboard/i);
  await page.context().storageState({ path: './.auth/sitepanel.json' });
});
