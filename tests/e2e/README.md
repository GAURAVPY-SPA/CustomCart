# End-to-end tests (Playwright)

Browser tests for the website and the Sitepanel, run against the local WAMP
site and `php spark serve`. They create their own test records
(`pw-test+…@example.com`, titles starting with `PW-TEST`) and remove them when
they finish.

## Setup (once)

```bat
cd tests\e2e
npm install
npx playwright install chromium
```

## Run

1. Start the Sitepanel server from the project root: `php spark serve --host localhost --port 8081`
2. Run the tests with a Sitepanel super admin login:

   ```bat
   cd tests\e2e
   set SP_USER=admin
   set SP_PASS=your-password
   npx playwright test
   npx playwright show-report
   ```

| Variable | Default |
|---|---|
| `FRONT_URL` | `http://localhost/customcart/` |
| `ADMIN_URL` | `http://localhost:8081/` |
| `SP_USER`, `SP_PASS` | none (needed by the Sitepanel projects) |
| `MYSQL_BIN` | `D:/wamp64/bin/mysql/mysql8.4.7/bin/mysql.exe` (checks saved records, removes test data) |
| `DB_NAME` | `customcart` |

Run one part: `npx playwright test --project=frontend` (also `accounts`, `responsive`, `sitepanel`, `sitepanel-crud`).
`shop` and `events` change store settings and save test records, so they run after the others; alone: `npx playwright test --project=shop --no-deps` (or `--project=events`).

## What is covered

| Spec | Covers |
|---|---|
| `front-crawl` | Every `wl_meta_tags` address and every internal link and asset reachable from them (404 audit, PHP errors, old branding). Report: `test-results/front-crawl.json` |
| `front-pages` | Each page of the `html/` design at its real route in a browser; header, footer and sitemap links; listing → detail links |
| `front-forms` | Contact, newsletter, testimonial, product enquiry, donation and course forms |
| `account-user` | Register, login/logout, every My Account page, edit account, change password, wishlist, cart → checkout, forgot/reset password, role limits |
| `account-volunteer` | Volunteer registration with photo, login/logout, every volunteer page, edit account, change password, course registration, role limits |
| `account-ui` | Account pages next to the `html/` reference: screenshots and class differences in `test-results/account-ui/` |
| `responsive` | Key pages at 1440, 820 and 390 px: no sideways scroll, menu or drawer |
| `sitepanel-crawl` | Every Sitepanel section (also those hidden from the sidebar) and its add/edit/view pages |
| `shop-settings` | Settings > Online store (shipping charge, free delivery threshold, GST): form validation, cart and checkout amounts, a placed order's saved amounts, GST of older orders kept. Restores the settings |
| `shop-order-reference` | Order URLs use the md5 reference, never the invoice number (confirmation, account order, printable invoice); other sessions and customers get 404; old invoice-number links redirect only for the owner. Places and removes test orders |
| `events-module` | Event page against `html/events-details.htm`; Sitepanel document upload (validation, several at once, replace, switch off, remove) and the documents on that event's page only; guest and member registration (validation, duplicates, account details, impersonation attempts); Manage Event Registrations (filters, details, status, delete); access, CSRF, closed / inactive events; the page and form at three widths. Removes its data |
| `mail-transports` | The mail method of `.env` (`mail.method`): PHP `mail()`, SMTP and the SendGrid API each send through themselves only (local SMTP sink and API stand-in on ports 25 / 2525 / 8099, no real email), empty method = `mail()`, failures reported without fallback, invalid settings refused; Sitepanel enquiry Send Mail shows `Email sent successfully.` or an error |
| `sitepanel-crud` | Access limits, login errors, validation, add with image upload, search, edit, status, delete, CKEditor + `wl_meta_tags` (News), pagination and filters, settings save, logout |

The captcha code of the public forms is read from the PHP session file in
`writable/session/`, so the tests must run on the same machine as the site.
