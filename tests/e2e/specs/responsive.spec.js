// Key pages at desktop, tablet and mobile widths: no sideways scrolling, the
// right navigation for the width (menu bar or drawer), no JS errors.
// Screenshots: test-results/responsive/<page>-<device>.png
const fs = require('fs');
const path = require('path');
const { test, expect } = require('@playwright/test');
const { watch, expectClean, horizontalOverflow } = require('../helpers');

const OUT = path.join(__dirname, '..', 'test-results', 'responsive');
const DEVICES = { desktop: { width: 1440, height: 900 }, tablet: { width: 820, height: 1180 }, mobile: { width: 390, height: 844 } };
const PAGES = ['', 'learning-courses', 'events-activities', 'news-announcements', 'blogs', 'success-stories', 'our-team', 'online-store',
  'books-media', 'basic-theory-study-planner', 'cart', 'contactus', 'login', 'register', 'volunteer-training-registration', 'donations',
  'images-gallery', 'videos-gallery', 'testimonials', 'sitemap', 'blogs/categories'];

for (const [device, viewport] of Object.entries(DEVICES)) {
  test.describe(device, () => {
    test.use({ viewport, hasTouch: device !== 'desktop', isMobile: device === 'mobile' });
    for (const route of PAGES) {
      test(`/${route}`, async ({ page }) => {
        const log = watch(page);
        await page.goto(route, { waitUntil: 'load' });
        await page.waitForTimeout(300);
        const o = await horizontalOverflow(page);
        expect.soft(o.scrollWidth, `sideways scroll on /${route} at ${viewport.width}px: ${o.offenders.join(', ')}`).toBeLessThanOrEqual(o.width + 1);
        // On a phone, wide content stretches the layout viewport itself (and the check above then passes).
        expect.soft(await page.evaluate(() => window.innerWidth), `layout viewport of /${route}`).toBe(viewport.width);
        if (device === 'desktop') {
          await expect.soft(page.locator('.main-nav')).toBeVisible();
        } else {
          await expect.soft(page.locator('.nav-toggle')).toBeVisible();
          await page.locator('.nav-toggle').click();
          await expect.soft(page.locator('#mobileNav')).toHaveClass(/show/);
          await page.keyboard.press('Escape');
        }
        fs.mkdirSync(OUT, { recursive: true });
        await page.screenshot({ path: path.join(OUT, `${route.replace(/\//g, '_') || 'home'}-${device}.png`) });
        expectClean(log);
      });
    }
  });
}
