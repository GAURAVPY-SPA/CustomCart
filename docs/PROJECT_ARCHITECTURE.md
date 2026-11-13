# Project Architecture

Reference for the CodeIgniter 4 website and Sitepanel in `D:\wamp64\www\customcart`.
Everything below describes the code as it currently exists; paths are relative
to the project root.

Related documents:

- [MVC_DEVELOPMENT_GUIDE.md](MVC_DEVELOPMENT_GUIDE.md) — how to build a module (worked example: `teams`)
- [DYNAMIC_ROUTING_AND_SEO.md](DYNAMIC_ROUTING_AND_SEO.md) — `wl_meta_tags`, URL resolution, SEO tags, static pages
- [MODULE_GENERATOR_COMMANDS.md](MODULE_GENERATOR_COMMANDS.md) — `php spark make:module` and `make:module-route`
- [../README.md](../README.md) — installation, `.env`, base URL, deployment, troubleshooting

## 1. Versions

| Component | Version | Where it is set |
|---|---|---|
| PHP | 8.2 or newer (`composer.json`: `"php": "^8.2"`); development runs PHP 8.5 (WAMP) | `composer.json` |
| CodeIgniter | 4.7.5 (`"codeigniter4/framework": "^4.7"`) | `composer.json`, `vendor/codeigniter4/framework` |
| MySQL | 8.4 (development) | `.env` → `database.default.*` |
| Front-end (website) | Bootstrap 5.3.1 + Bootstrap Icons (CDN), theme CSS in `public/assets/designer/` | `app/Views/top_application.php` |
| Front-end (Sitepanel) | Bootstrap (local copy), CKEditor 4 (local copy) | `public/assets/vendor/` |

No other Composer packages are used at runtime (PHPUnit, Faker and vfsStream are dev-only).

## 2. Root directory

```
customcart/
├── app/                 Application code shared by all modules (config, base classes, libraries, helpers, layout views)
├── modules/             HMVC modules: one folder per website feature + the Sitepanel (admin)
├── public/              Web root: index.php (front controller), .htaccess, assets/, favicon.ico
├── uploads/             Uploaded images (outside the web root; served by App\Controllers\Uploads)
├── writable/            cache/, logs/, session/ (must be writable by the web server)
├── vendor/              Composer packages (CodeIgniter framework)
├── tests/               PHPUnit tests and support files
├── docs/                This documentation (and README images)
├── builds, spark, env, preload.php, phpunit.dist.xml, composer.json/.lock
└── customcart.sql   Database dump used for the initial import
```

`spark` is the CodeIgniter CLI (`php spark <command>`). `env` is the template for `.env`.

## 3. `app/` — shared application code

```
app/
├── Commands/
│   ├── MakeModule.php            php spark make:module       (new website module + wl_meta_tags row)
│   └── MakeModuleRoute.php       php spark make:module-route (register a copied module)
├── Config/                       CodeIgniter config + project config (see §8)
│   ├── Routes.php                image routes (uploads/...)
│   ├── ModuleRoutes.php          404 override + the wl_meta_tags URL resolver
│   ├── Routing.php               $routeFiles = [Routes.php, ModuleRoutes.php]
│   ├── Autoload.php              PSR-4 namespaces of the modules, autoloaded helpers
│   ├── Filters.php               csrf (global), sitepanelauth alias
│   ├── Uploads.php               upload directory, folders, MIME types, size limit
│   ├── SiteMenu.php              header / footer links of the website
│   ├── Mail.php                  outgoing mail settings
│   └── App.php, Database.php, Session.php, Security.php, Cookie.php, ...
├── Controllers/
│   ├── BaseController.php        base of every controller (site settings, helpers, boot()/init() hooks)
│   ├── PublicController.php      base of website controllers (maintenance mode, render(), 404)
│   ├── AdminController.php       base of Sitepanel controllers (auth, permissions, list/status/delete/bulk helpers)
│   └── Uploads.php               serves uploads/<folder>/<file>
├── Database/
│   ├── Migrations/               2026-10-30-140000_CreateFaqTable.php ... 2026-10-31-110000_AddCmsPageLayout.php
│   └── Seeds/                    DatabaseSeeder, OtherManagementSectionsSeeder, CountrySeeder, PageMetaTagsSeeder
├── Exceptions/HaltException.php  ends a request with a ready response (maintenance page)
├── Helpers/                      app_helper, utils_helper, blog_helper
├── Language/en/Validation.php    validation messages
├── Libraries/                    FormValidation, HtmlSanitizer, ImageUpload, Mailer, PageMeta, SiteSettings
├── Models/                       BaseModel, SettingsModel, UtilsModel
└── Views/                        website layout: top_application, bottom_application, project_header,
                                  project_footer, includes/seo_head, page_not_found, maintenance_mode
```

## 4. `modules/` — HMVC modules

Every module is a PSR-4 namespace registered in `app/Config/Autoload.php`:

```php
'Modules\Sitepanel'    => ROOTPATH . 'modules/sitepanel',
'Modules\Blog'         => ROOTPATH . 'modules/blog',
'Modules\Testimonials' => ROOTPATH . 'modules/testimonials',
'Modules\Pages'        => ROOTPATH . 'modules/pages',
'Modules\Home'         => ROOTPATH . 'modules/home',
'Modules\Teams'        => ROOTPATH . 'modules/teams',
```

### 4.1 Website modules

A website module contains **only** `Controllers/`, `Models/` and `Views/`.
Anything shared (helpers, libraries, base classes, layout) lives in `app/`.

```
modules/<module>/                 folder: snake_case            e.g. modules/testimonials
├── Controllers/<Module>.php      class <Module> extends App\Controllers\PublicController
├── Models/<Name>Model.php        class <Name>Model extends App\Models\BaseModel
└── Views/<view>.php              page views; each includes the site layout itself
```

| Module | Controller(s) | Models | Views | Addresses |
|---|---|---|---|---|
| `home` | `Home` | `HomeModel` | `home.php` | `/`, `/home` |
| `pages` | `Pages` | `PagesModel` | `cms_page.php` (page text) + designed views `about_us.php`, `contact_us.php`, `sitemap.php`, `privacy_policy.php`, `terms.php`, `team_*.php` | each static page's address; 404 page |
| `blog` | `Blog` | `BlogModel`, `BlogCategoryModel`, `BlogCommentModel` | `blog_listing.php`, `blog_categories.php`, `blog_detail.php`, `blog_sidebar.php` | `/blogs`, `/blogs/categories`, `/blogs/<slug>` |
| `testimonials` | `Testimonials` | `TestimonialModel` | `testimonials.php` | `/testimonials` |
| `teams` | `Teams` | `TeamsModel` | `teams.php` | `/teams` (generated by `make:module`; starting point) |

Website addresses are not declared in route files: they are rows of
`wl_meta_tags` (see [DYNAMIC_ROUTING_AND_SEO.md](DYNAMIC_ROUTING_AND_SEO.md)).

### 4.2 Sitepanel module (admin)

```
modules/sitepanel/
├── Commands/CreateAdmin.php      php spark sitepanel:create-admin
├── Config/
│   ├── Routes.php                every sitepanel/<section>/<action>/<id> URL (found by module discovery)
│   ├── Services.php              adminAuth, adminAccess, sitepanelTheme services
│   └── Sitepanel.php             every Sitepanel setting: admin password rules, banner positions,
│                                 dashboard options, theme colours / card gradients / presets
├── Controllers/                  one controller per section, all extend AdminController:
│                                 Dashboard, Settings, Theme, ChangePassword, Blogs, BlogCategories,
│                                 BlogComments, Testimonial, Enquiry, ProductEnquiry, Coupons,
│                                 HeaderImages, Faq, Subadmin, Location, Meta, Banners, Staticpages,
│                                 Newsletter; Sitepanel (sign-in / sign-out, extends BaseController)
├── Filters/SitepanelAuth.php     route filter "sitepanelauth": signed-in active administrator
├── Helpers/
│   ├── sitepanel_helper.php      sp_view(), sp_icon(), sp_section_icon(), sp_datetime(), sp_theme_style(), sp_slug()
│   └── admin_helper.php          admin_pagination()
├── Libraries/                    AdminAuth, AdminAccess, SitepanelTheme, NewsletterMailer, EnquiryMailer
├── Models/                       AdminModel, SectionModel, MetaModel, FaqModel, BannerModel, CouponModel,
│                                 CountryModel, CityModel, EnquiryModel, HeaderImagesModel, NewsletterModel,
│                                 SubadminModel, DashboardModel
└── Views/
    ├── includes/                 header, footer, form_errors, bulk_actions, row_actions, list_foot, stat_card
    ├── dashboard/                admin_login, admin_left_view (sidebar), dashbord_index_view, ...
    └── <section>/                e.g. faq/view_faq_list.php, faq/view_faq_form.php
```

The Sitepanel reuses some website models (e.g. `Modules\Pages\Models\PagesModel`,
`Modules\Blog\Models\BlogModel`) rather than duplicating queries.

## 5. Request lifecycle

```
public/index.php
  └─ CodeIgniter boots, loads .env and app/Config/*
  └─ Routing: app/Config/Routes.php, app/Config/ModuleRoutes.php, modules/sitepanel/Config/Routes.php
       ModuleRoutes.php: if the path is not a declared route, look it up once in wl_meta_tags.page_url
                         and add a route to the controller its entity_type names (see routing doc)
  └─ Filters: csrf (every POST, except uploads/*), sitepanelauth (sitepanel routes)
  └─ Controller::initController() → boot() → init() → action
       BaseController   loads site settings into $view_data
       PublicController boot(): maintenance mode check
       AdminController  boot(): sign-in, section permission, sidebar menu
  └─ View: website pages echo view('top_application') ... view('bottom_application');
           Sitepanel pages echo sp_view('includes/header') ... sp_view('includes/footer')
  └─ Unknown address: Pages::not_found (404 override) → designed 404 page
```

## 6. Controllers

| Base class | File | Used by | Provides |
|---|---|---|---|
| `BaseController` | `app/Controllers/BaseController.php` | everything | `$view_data` (site_name, site_title, site_logo, site_favicon, copyright, footer_description), `$site_settings`, `boot()` and `init()` hooks, `trim_post()` |
| `PublicController` | `app/Controllers/PublicController.php` | website controllers | maintenance mode (503 page), `render($view, $data)`, `not_found_page()` (throws 404), `render_not_found()` |
| `AdminController` | `app/Controllers/AdminController.php` | Sitepanel sections | sign-in + permission check, `render()` (sitepanel views + privileges), `PAGESIZE` (20), `current_page()`, `record_or_back()`, `require_post()`, `require_privilege()`, `update_status()`, `delete_record()`, `process_bulk_action()`, `bulk_delete_checked()`, `back()` |

Conventions:

- Override `init()` (not the constructor) to create models and libraries.
- Website views are addressed with their full namespace: `$this->render('Modules\Testimonials\Views\testimonials', $data)`.
- Sitepanel views are addressed relative to `modules/sitepanel/Views/`: `$this->render('faq/view_faq_list', $data)`.
- Redirects use `redirect_now($uri)` (ends the request immediately, like CI3 `redirect()`).
- Flash messages: `$this->session->setFlashdata('sitepanel_success' | 'sitepanel_error' | 'sitepanel_notice', ...)`.

## 7. Models

All models extend `App\Models\BaseModel` (not `CodeIgniter\Model`: models here work
on several tables, like the CI3 `MY_Model`).

Return contract: **`false`** = the database is unavailable or the query failed
(the error is logged), **`null`** = no matching row, otherwise the data.

| Method | Purpose |
|---|---|
| `find($table, $where)` | one row (`array\|null\|false`) |
| `findCount($table, $where)` | count (`int\|false`) |
| `safe_insert($table, $data)` | new id or `false` |
| `safe_update($table, $data, $where)`, `safe_delete($table, $where)` | `bool` |
| `paged_rows($table, $order, $offset, $limit, $filter)` (protected) | one list page; total in `$this->total_rec_found` |
| `find_many`, `safe_update_many`, `safe_delete_many` (protected) | by a list of ids (bulk actions) |
| `table($name)`, `single_row()`, `all_rows()`, `db_ready()`, `write_failed()` (protected) | query builder access with the same error handling |

### Standard CRUD model structure

Reference: `modules/brochure/Models/BrochureModel.php` (the `make:module` template).
A model for a Sitepanel-managed table has:

```php
class FaqModel extends BaseModel
{
    public const TABLE       = 'wl_faq';   // used in every query instead of the table name
    public const PRIMARY_KEY = 'faq_id';   // the real primary key (not always "id")

    public function get_faq(int $offset, int $per_page, string $keyword = '', string $status = '') { /* paged_rows() */ }
    public function get_faq_by_id($id)            { return $this->find(self::TABLE, [self::PRIMARY_KEY => (int) $id]); }
    public function get_faqs_by_ids(array $ids)   { return $this->find_many(self::TABLE, self::PRIMARY_KEY, $ids); }
    public function bulk_update_status(array $ids, string $status): bool { return $this->safe_update_many(self::TABLE, self::PRIMARY_KEY, $ids, ['status' => $status]); }
    public function bulk_delete(array $ids): bool { return $this->safe_delete_many(self::TABLE, self::PRIMARY_KEY, $ids); }
    // + the module's own methods (get_active_faqs(), question_exists(), ...)
}
```

Naming:

- the list method is `get_<items>()`;
- one row is `get_<item>_by_id()` (singular);
- bulk actions use `get_<items>_by_ids()` (plural);
- checks are `<field>_exists()` / `<field>_taken()`.

`AdminController::process_bulk_action()` calls `get_<items>_by_ids()` (name passed by
the controller), `bulk_update_status()` and `bulk_delete()`. Other tables a model
reads use the other model's constant (`BlogModel::TABLE`, `CountryModel::TABLE . ' AS c'`).

| Model | `TABLE` | `PRIMARY_KEY` | By id / by ids | Notes |
|---|---|---|---|---|
| `Blog\BlogModel` | `wl_blogs` | `blog_id` | `get_blog_by_id` / `get_blogs_by_ids` | also `RELATED_TABLE` (`wl_blog_related`) |
| `Blog\BlogCategoryModel` | `wl_blog_categories` | `category_id` | `get_category_by_id` / `get_categories_by_ids` | delete refused while a category has posts (controller) |
| `Blog\BlogCommentModel` | `wl_blog_comments` | `comment_id` | `get_comment_by_id` / `get_comments_by_ids` | status change also sets `updated_at` |
| `Pages\PagesModel` | `wl_cms_pages` | `page_id` | `get_cms_page_by_id` / `get_cms_pages_by_ids` | also `META_TABLE`; delete = status `2` + meta row removed |
| `Testimonials\TestimonialModel` | `wl_testimonial` | `testimonial_id` | `get_testimonial_by_id` / `get_testimonials_by_ids` | list arguments `(limit, offset)` as in the reference |
| `Sitepanel\BannerModel` | `wl_banners` | `banner_id` | `get_banner_by_id` / `get_banners_by_ids` | |
| `Sitepanel\CountryModel` | `wl_countries` | `country_id` | `get_country_by_id` / `get_countries_by_ids` | |
| `Sitepanel\CityModel` | `wl_state` | `id` | `get_city_by_id` / `get_cities_by_ids` | bulk methods limited to `$country_id` |
| `Sitepanel\CouponModel` | `wl_coupons` | `coupon_id` | `get_coupon_by_id` / `get_coupons_by_ids` | rows with status `2` left out |
| `Sitepanel\FaqModel` | `wl_faq` | `faq_id` | `get_faq_by_id` / `get_faqs_by_ids` | |
| `Sitepanel\HeaderImagesModel` | `wl_header_images` | `id` | `get_header_image_by_id` / `get_header_images_by_ids` | |
| `Sitepanel\NewsletterModel` | `wl_newsletters` | `subscriber_id` | `get_subscriber_by_id` / `get_subscribers_by_ids` | old names `get_record_by_id` / `get_records_by_ids` kept as deprecated aliases |
| `Sitepanel\SubadminModel` | `tbl_admin` | `admin_id` | `get_subadmin_by_id` / `get_subadmins_by_ids` | only sub-admins (`admin_type > 1`) |
| `Sitepanel\EnquiryModel` | `wl_enquiry` | `id` | `get_enquiry_by_id` / `get_enquiries_by_ids` | no status column: reply status instead, no `bulk_update_status()` |
| `Teams\TeamsModel` | `wl_teams` | `team_id` | `get_team_by_id` / `get_teams_by_ids` | table not created yet (schema in the MVC guide) |
| `Brochure\BrochureModel` | `wl_brochure` | `brochure_id` | `get_brochure_by_id` / `get_brochures_by_ids` | reference; table not created yet |

Specialised models keep their own structure, with no CRUD bulk methods:

- `AdminModel` (sign-in);
- `MetaModel` (URL registry);
- `SectionModel` (menu and permissions);
- `DashboardModel` (counts);
- `HomeModel`;
- `SettingsModel`, `UtilsModel`.

## 8. Configuration

Settings and where they live are listed in the README
([Centralized configuration](../README.md#centralized-configuration)). Project-specific
config classes:

| File | Contents |
|---|---|
| `app/Config/Uploads.php` | `$directory` (`ROOTPATH . 'uploads/'`), `$urlPrefix` (`uploads/`), `$folders` (folder → table columns using it), `$mimes`, `$maxKb` (2048), `$cacheSeconds` |
| `app/Config/SiteMenu.php` | header top bar, main menu and footer links; a link shows only once its page exists |
| `app/Config/Mail.php` | `mail.method` (`mail`, `smtp`, `ci_email`) and SMTP settings |
| `modules/sitepanel/Config/Sitepanel.php` | every Sitepanel setting, by section: admin password rules, banner positions, dashboard options (`showDashboardOrdersGraph`), theme colour defaults, card gradients and presets (colours overridden by Theme Management); `.env`: `sitepanel.<property>` |
| `tbl_site_settings` (database) | website name, contact details, social links, SEO defaults, maintenance mode (Sitepanel → Admin Settings; defaults in `app/Libraries/SiteSettings.php`) |

`.env` holds the environment, base URL, database and mail credentials. It is
not changed by any code or command in this project.

## 9. Libraries and helpers

| Library (`app/Libraries`) | Service / use |
|---|---|
| `SiteSettings` | `service('siteSettings')`: `get($key)`, `logo_path()`, `favicon_path()`, `maintenance_mode()` |
| `PageMeta` | `service('pageMeta')->head($page)`: SEO tags from `wl_meta_tags` (used by `seo_head()`) |
| `FormValidation` | CI3-style validation: `set_rules($field, $label, $rules, $errors)`, closures `['name', fn]`, `run()`, `error_array()` |
| `HtmlSanitizer` | `clean($html)`: allow-list sanitizer for CKEditor HTML |
| `ImageUpload` | `save($field, $folder, $label, $max_size)`, `delete()`, `path()`; checks size, MIME type and image content |
| `Mailer` | sends mail with the configured method |

| Library (`modules/sitepanel/Libraries`) | Use |
|---|---|
| `AdminAuth` (`service('adminAuth')`) | sign-in, password check, login throttling, idle timeout, `require_login()` |
| `AdminAccess` (`service('adminAccess')`) | privileges per section, sidebar menu |
| `SitepanelTheme` | Sitepanel colours (CSS variables) |
| `NewsletterMailer`, `EnquiryMailer` | bulk mail from the Sitepanel |

| Helper | Functions |
|---|---|
| `app_helper` (autoloaded) | `asset_url()`, `website_url()`, `theme_url()`, `resource_url()`, `seo_head()`, `html_escape()`, `redirect_now()`, `form_error()`, `set_checkbox()`, `set_radio()`, `site_menu_url()`, `site_menu_links()`, `phone_digits()`, `map_embed_src()` |
| `utils_helper` (autoloaded) | date and array utilities from the reference project |
| `blog_helper` | `blog_date()`, `blog_read_minutes()`, `blog_tags()` |
| `sitepanel_helper`, `admin_helper` (Sitepanel controllers) | see §4.2 |

## 10. Views and layouts

**Website** (`app/Views/`):

```php
<?php echo view('top_application'); ?>      <!-- <head> with seo_head($page_meta), CSS, project_header -->
... page content ...
<?php echo view('bottom_application'); ?>   <!-- project_footer, scripts -->
```

`$page_meta` passed by the controller gives the page's default title and
description; the `wl_meta_tags` row overrides them.

**Sitepanel** (`modules/sitepanel/Views/`):

```php
<?php echo sp_view('includes/header'); ?>   <!-- top bar, sidebar (dashboard/admin_left_view), breadcrumbs, flash messages -->
... panels, forms, tables ...
<?php echo sp_view('includes/footer'); ?>
```

Reusable Sitepanel partials: `includes/form_errors` (error list), `includes/bulk_actions`
(Active / Deactive / Delete toolbar), `includes/row_actions` (View / Edit / Activate / Delete
per row), `includes/list_foot` (total + pagination), `includes/stat_card` (dashboard card).

Sitepanel CSS classes (in `public/assets/sitepanel/css/sitepanel.css`): `sp-panel`,
`sp-table`, `sp-filters`, `sp-badge--ok` / `--muted`, `sp-btn-primary`, `sp-btn-outline`,
`sp-link` (text links), `sp-link--external`, `sp-link-count`, `sp-muted`.

## 11. Database

- Connection: `.env` → `database.default.*` (defaults in `app/Config/Database.php`).
- Initial schema and data: `customcart.sql`.
- Later changes: migrations in `app/Database/Migrations` (`php spark migrate`).
- Required rows: seeders in `app/Database/Seeds` (`php spark db:seed DatabaseSeeder`, safe to run again).

Main tables:

| Table | Used by |
|---|---|
| `wl_meta_tags` | URL registry + SEO data of every website page ([routing doc](DYNAMIC_ROUTING_AND_SEO.md)) |
| `wl_cms_pages` | static pages (Manage Static Pages) |
| `wl_blogs`, `wl_blog_categories`, `wl_blog_comments` | blog |
| `wl_testimonial`, `wl_banners`, `wl_header_images`, `wl_faq`, `wl_enquiry`, `wl_newsletters`, `wl_coupons`, `wl_coupon_customers`, `wl_countries`, `wl_state` | the matching modules |
| `tbl_admin`, `tbl_admin_sections`, `tbl_admin_allowed_sections`, `tbl_admin_login_log` | Sitepanel accounts, menu, sub-admin permissions, sign-in log |
| `tbl_site_settings` | website settings, Sitepanel theme |

Status columns use `'1'` = active, `'0'` = inactive (`wl_cms_pages` also `'2'` = deleted).

## 12. Assets

| Path | URL | Helper |
|---|---|---|
| `public/assets/designer/themes/default/` (`css/style.css`, `images/`) | `assets/designer/themes/default/` | `theme_url()` |
| `public/assets/designer/resources/` (`Scripts/`) | `assets/designer/resources/` | `resource_url()` |
| `public/assets/sitepanel/` (`css/sitepanel.css`, `js/`) | `assets/sitepanel/` | `asset_url()` |
| `public/assets/vendor/` (`bootstrap/`, `ckeditor4/`) | `assets/vendor/` | `asset_url()` |
| `public/assets/blog/js/` | `assets/blog/js/` | `asset_url()` |

`asset_url()` appends `?v=<modification time>` for cache busting. Fonts load from
Google Fonts. Never hard-code the base URL; use the helpers.

## 13. Uploads

- Files are stored in `uploads/<folder>/` at the project root (outside `public/`).
- Folders are declared in `app/Config/Uploads.php` → `$folders`: `banner`, `blog`, `header_images`, `testimonial`, `settings`.
- Files are served by `App\Controllers\Uploads::serve` at `uploads/<folder>/<file>`.
- Old `uploaded_files/...` links redirect to it (`legacy`).
- Uploads go through `ImageUpload::save()`, which checks the extension, the real MIME type, image content and size (`$maxKb`).
- `ImageUpload::delete()` only removes a file that no other row references (checked against `$folders`).

A new upload folder is added to `$folders` with the table column(s) that store its file names.

## 14. Authentication and sessions

- **Sessions:** files in `writable/session/`, cookie `customcart_session` (`.env` sets `customcart_ci4_session`), `expiration` 7200 s (`app/Config/Session.php`).
- **CSRF:** cookie method, token field `csrf_customcart`. Applied to every POST (`app/Config/Filters.php`); forms use `form_open()`.
- **Sitepanel sign-in:** `Sitepanel::login` → `AdminAuth::attempt()`.
  - Passwords are checked with `password_verify`.
  - Failures are throttled per username: 5 failures (`MAX_FAILURES_PER_USERNAME`) within `THROTTLE_WINDOW_SECONDS` (900 s).
  - The session ID is regenerated, and `sitepanel_admin_id` is stored in the session.
- **Every protected request:**
  1. The `sitepanelauth` filter and `AdminController::boot()` call `AdminAuth::require_login()`.
  2. It applies a server-side idle timeout (session `expiration`) and checks the account is still active.
  3. `AdminAccess` loads the section's privileges.
- **Privileges** (`AdminAccess::PRIV_*`): 1 view, 2 add, 3 edit, 4 activate, 5 deactivate, 6 delete.
  - A section is matched by `snake_case(ControllerClass)` = `tbl_admin_sections.section_controller`.
  - The super admin has every privilege; `dashboard` and `change_password` are always allowed.
- **Website:** no visitor accounts. Forms (contact, comments, testimonials) use CSRF, a honeypot field and per-IP limits.

## 15. Project-specific vs reusable

| Project-specific (this site only) | Reusable conventions |
|---|---|
| Table names (`wl_*`, `tbl_*`) and their columns | Base controller split: Base / Public / Admin |
| `wl_meta_tags` as URL registry: `<module>/<method>` + `entity_id` (static pages `pages/index/<url>`, posts `blog/detail`) | `BaseModel` return contract (`false` / `null` / data) |
| Designed page views (`Pages::DESIGNED_VIEWS`) | One folder per module: `Controllers/`, `Models/`, `Views/` |
| Sitepanel sections, `tbl_admin_sections` rows, privilege codes | Thin controllers, queries in models, escaping in views (`html_escape`) |
| Theme paths under `public/assets/designer/` | Registering URLs in data instead of route files, with a resolver |
| `make:module` templates (they use this project's layout and base classes) | Generator safety rules: validate, never overwrite, roll back |

### Using these conventions in another project

- **Inspect first.** Check the folder structure, base classes, route files and database schema before applying anything from these documents. Another project may have no `wl_meta_tags` table, other column names, or other base controllers.
- **Another CodeIgniter 4 project:**
  1. Copy the *pattern*, not the files.
  2. Adapt `MetaModel::route_for()` and `ModuleRoutes.php` to that project's registry table.
  3. Adapt the generator templates to its base classes and layout.
  4. Check the CodeIgniter version (Spark command API, `$routes->match()`) and the PHP version. This code uses PHP 8.2+ syntax: typed properties, `never`, `str_starts_with()`, arrow functions, spread in arrays.
- **CodeIgniter 3 / PHP 5 projects:**
  - Spark commands, namespaces, `service()` and this PHP 8 code cannot be used there.
  - In CI3 the same registry is implemented in `application/core/MY_Router.php` (`_db_routes()`: `$route[page_url] = entity_type . '/' . entity_id`).
  - Modules are created by copying a module folder and inserting the `wl_meta_tags` row by SQL.
  - Write any helper script for the PHP version that project actually runs.
