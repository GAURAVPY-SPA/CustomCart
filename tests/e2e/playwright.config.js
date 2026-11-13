// Playwright end-to-end tests: the website (WAMP) and the Sitepanel (php spark serve).
//   FRONT_URL  website base URL   (default http://localhost/customcart/)
//   ADMIN_URL  Sitepanel base URL (default http://localhost:8081/)
//   SP_USER / SP_PASS  Sitepanel login used by the sitepanel specs
// Run from this folder: npx playwright test   (report: npx playwright show-report)
const { defineConfig, devices } = require('@playwright/test');

const FRONT_URL = process.env.FRONT_URL || 'http://localhost/customcart/';
const ADMIN_URL = process.env.ADMIN_URL || 'http://localhost:8081/';

module.exports = defineConfig({
  testDir: './specs',
  outputDir: './test-results',
  timeout: 90_000,
  expect: { timeout: 10_000 },
  fullyParallel: false,
  workers: 2,
  retries: 0,
  reporter: [['list'], ['html', { open: 'never', outputFolder: 'playwright-report' }], ['json', { outputFile: 'playwright-report/results.json' }]],
  use: {
    screenshot: 'only-on-failure',
    trace: 'retain-on-failure',
    ignoreHTTPSErrors: true,
  },
  projects: [
    { name: 'sitepanel-setup', testMatch: /sitepanel\.setup\.js/, use: { baseURL: ADMIN_URL } },
    { name: 'frontend', testMatch: /front-.*\.spec\.js/, use: { ...devices['Desktop Chrome'], baseURL: FRONT_URL } },
    { name: 'accounts', testMatch: /account-.*\.spec\.js/, use: { ...devices['Desktop Chrome'], baseURL: FRONT_URL } },
    { name: 'responsive', testMatch: /responsive\.spec\.js/, use: { baseURL: FRONT_URL } },
    // Both Sitepanel projects share one admin session; run one after the other so the
    // crawl's page loads do not use up the flash messages the CRUD tests check.
    {
      name: 'sitepanel',
      testMatch: /sitepanel-crawl\.spec\.js/,
      dependencies: ['sitepanel-setup'],
      use: { ...devices['Desktop Chrome'], baseURL: ADMIN_URL, storageState: './.auth/sitepanel.json' },
    },
    // Store settings and placed test orders (shop-*.spec.js): after every other project.
    // Alone: npx playwright test --project=shop --no-deps
    {
      name: 'shop',
      testMatch: /shop-.*\.spec\.js/,
      dependencies: ['frontend', 'accounts', 'responsive', 'sitepanel-crud'],
      use: { ...devices['Desktop Chrome'], baseURL: FRONT_URL },
    },
    // Events module (documents, registrations) and the mail transports (local SMTP / API stand-ins
    // on ports 25, 2525, 8099): they save test records, so they run after the others.
    // Alone: npx playwright test --project=events --no-deps
    {
      name: 'events',
      testMatch: /(events-.*|mail-transports)\.spec\.js/,
      dependencies: ['frontend', 'accounts', 'responsive', 'sitepanel-crud'],
      use: { ...devices['Desktop Chrome'], baseURL: FRONT_URL },
    },
    {
      name: 'sitepanel-crud',
      testMatch: /sitepanel-crud\.spec\.js/,
      dependencies: ['sitepanel'],
      use: { ...devices['Desktop Chrome'], baseURL: ADMIN_URL, storageState: './.auth/sitepanel.json' },
    },
    // Bulk toolbars above / below the lists: same admin session, so after the CRUD tests (flash messages).
    {
      name: 'sitepanel-bulk',
      testMatch: /sitepanel-bulk\.spec\.js/,
      dependencies: ['sitepanel-crud'],
      use: { ...devices['Desktop Chrome'], baseURL: ADMIN_URL, storageState: './.auth/sitepanel.json' },
    },
  ],
});
