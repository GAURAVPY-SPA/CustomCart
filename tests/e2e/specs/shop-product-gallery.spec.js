// Product page gallery against html/details.htm: main image and thumbnail sizes at three
// widths, thumbnail switching, the zoom modal, one-image products (no thumbnail strip) and
// the removed "Every purchase supports YSD programmes" line. The product is one that has a
// single image when the run starts; multi-image cases add temporary wl_products_media rows
// to it (existing image files) and remove exactly those rows (by id) again.
const { test, expect } = require('@playwright/test');
const { watch, expectClean, sql } = require('../helpers');

const FRONT_URL = process.env.FRONT_URL || 'http://localhost/customcart/';
const PRODUCT = { id: 0, url: '' }; // chosen in beforeAll
const EXTRA = ['demo-product-img1.jpg', 'demo-product-img2.jpg', 'demo-product-img4.jpg'];
const added = [];
const WIDTHS = { desktop: 1440, tablet: 820, mobile: 390 };

const boxes = (page) => page.evaluate(() => {
  const r = (e) => { const b = e.getBoundingClientRect(); return { x: Math.round(b.x), w: Math.round(b.width), h: Math.round(b.height) }; };
  const strip = document.querySelector('.product-thumbs');
  return {
    main: r(document.querySelector('.product-gallery-main')),
    thumbs: [...document.querySelectorAll('.product-thumb')].map(r),
    stripVisible: !!strip && getComputedStyle(strip).display !== 'none',
  };
});

async function reference(browser, width) {
  const page = await browser.newPage({ viewport: { width, height: 900 } });
  await page.goto(new URL('html/details.htm', FRONT_URL).href);
  const b = await boxes(page);
  await page.close();
  return b;
}

function addImages(files) {
  files.forEach((file, i) => {
    sql(`insert into wl_products_media (products_id, color_id, media_type, media, is_default, sort_order, media_date_added) values (${PRODUCT.id}, 0, 'photo', '${file}', 'N', ${900 + i}, now())`);
    added.push(Number(sql(`select max(id) from wl_products_media where products_id=${PRODUCT.id} and media='${file}'`)[0][0]));
  });
}
// Only the rows this test inserted (images added in the Sitepanel meanwhile are never touched).
const removeImages = () => {
  if (added.length) sql(`delete from wl_products_media where id in (${added.join(',')}) and products_id=${PRODUCT.id}`);
  added.length = 0;
};

test.describe.serial('product gallery', () => {
  test.beforeAll(() => {
    const rows = sql(`select p.products_id, m.page_url from wl_products p join wl_meta_tags m on m.entity_type='products/detail' and m.entity_id=p.products_id
      where p.status='1' and (select count(*) from wl_products_media x where x.products_id=p.products_id) = 1 order by p.products_id limit 1`);
    if (!rows.length) throw new Error('No active product with exactly one image to test the gallery with.');
    [PRODUCT.id, PRODUCT.url] = [Number(rows[0][0]), rows[0][1]];
  });
  test.afterAll(removeImages);

  test('"Every purchase supports YSD programmes" is gone from product pages', async ({ page }) => {
    for (const [url] of sql(`select m.page_url from wl_meta_tags m join wl_products p on p.products_id=m.entity_id where m.entity_type='products/detail' and p.status='1'`)) {
      await page.goto(url);
      await expect(page.locator('main'), url).not.toContainText('Every purchase supports YSD programmes');
      await expect(page.locator('.product-perks'), url).toContainText('Product delivery in 3–5 working days'); // the other line stays
    }
  });

  for (const [device, width] of Object.entries(WIDTHS)) {
    test(`one image at ${device}: main image as the design, no thumbnail strip, zoom works`, async ({ browser, baseURL }) => {
      const ref = await reference(browser, width);
      const page = await browser.newPage({ baseURL, viewport: { width, height: 900 } });
      const log = watch(page);
      await page.goto(PRODUCT.url);
      const live = await boxes(page);
      expect(live.main).toEqual(ref.main);
      expect(live.stripVisible, 'one image: no thumbnails').toBe(false);
      await expect(page.locator('#productMainImage')).toHaveJSProperty('complete', true);
      expect(await page.locator('#productMainImage').evaluate((i) => i.naturalWidth)).toBeGreaterThan(0);
      await page.locator('#productZoom').click();
      await expect(page.locator('#productZoomModal')).toBeVisible();
      await expect(page.locator('#productZoomImage')).toHaveAttribute('src', await page.locator('#productMainImage').getAttribute('src'));
      expectClean(log);
      await page.close();
    });
  }

  test('several images: thumbnails sized and placed as the design, switching and zoom', async ({ browser, baseURL }) => {
    addImages(EXTRA); // 4 images, as the design
    for (const [device, width] of Object.entries(WIDTHS)) {
      const ref = await reference(browser, width);
      const page = await browser.newPage({ baseURL, viewport: { width, height: 900 } });
      const log = watch(page);
      await page.goto(PRODUCT.url);
      const live = await boxes(page);
      expect(live.main, `${device} main`).toEqual(ref.main);
      expect(live.stripVisible).toBe(true);
      expect(live.thumbs.length).toBe(4);
      live.thumbs.forEach((t, i) => {
        expect(Math.abs(t.w - ref.thumbs[i].w), `${device} thumb ${i + 1} width ${t.w} vs ${ref.thumbs[i].w}`).toBeLessThanOrEqual(1);
        expect(Math.abs(t.h - ref.thumbs[i].h), `${device} thumb ${i + 1} height`).toBeLessThanOrEqual(1);
        expect(Math.abs(t.x - ref.thumbs[i].x), `${device} thumb ${i + 1} position`).toBeLessThanOrEqual(1);
      });
      if (device === 'desktop') {
        // Each thumbnail shows its image in the main slot.
        const thumbs = page.locator('.product-thumb');
        for (let i = 0; i < 4; i++) {
          await thumbs.nth(i).click();
          await expect(page.locator('#productMainImage')).toHaveAttribute('src', await thumbs.nth(i).getAttribute('data-image'));
          await expect(thumbs.nth(i)).toHaveClass(/is-active/);
          await expect(thumbs.nth(i)).toHaveAttribute('aria-pressed', 'true');
        }
        const broken = await page.$$eval('.product-gallery img', (imgs) => imgs.filter((i) => i.complete && i.naturalWidth === 0).map((i) => i.src));
        expect(broken).toEqual([]);
        // Zoom modal opens on the chosen image and counts all four.
        await page.locator('#productZoom').click();
        await expect(page.locator('#productZoomModal')).toBeVisible();
        await expect(page.locator('#productZoomCount')).toContainText('4');
        await expect(page.locator('#productZoomImage')).toHaveAttribute('src', await thumbs.nth(3).getAttribute('data-image'));
      }
      expectClean(log);
      await page.close();
    }
  });

  test('two images keep the design thumbnail size (no stretching)', async ({ browser, baseURL }) => {
    removeImages();
    addImages(EXTRA.slice(0, 1)); // 2 images
    const ref = await reference(browser, 1440);
    const page = await browser.newPage({ baseURL, viewport: { width: 1440, height: 900 } });
    await page.goto(PRODUCT.url);
    const live = await boxes(page);
    expect(live.thumbs.length).toBe(2);
    live.thumbs.forEach((t, i) => expect(Math.abs(t.w - ref.thumbs[i].w), `thumb ${i + 1} ${t.w} vs ${ref.thumbs[i].w}`).toBeLessThanOrEqual(1));
    await page.locator('.product-thumb').nth(1).click();
    await expect(page.locator('#productMainImage')).toHaveAttribute('src', await page.locator('.product-thumb').nth(1).getAttribute('data-image'));
    await page.close();
  });
});
