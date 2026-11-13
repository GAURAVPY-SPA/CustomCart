# Dynamic Routing and SEO (`wl_meta_tags`)

The table `wl_meta_tags` has two jobs:

1. It is the **URL registry**: every page and module address of the website is a row, and the router resolves each request through it.
2. It holds the **SEO data** (title, description, keywords) of those pages.

This is the mechanism of the reference CodeIgniter 3 project (`MY_Router::_db_routes()`),
reimplemented in CodeIgniter 4. No website module needs a line in a route file.

See also: [PROJECT_ARCHITECTURE.md](PROJECT_ARCHITECTURE.md),
[MODULE_GENERATOR_COMMANDS.md](MODULE_GENERATOR_COMMANDS.md).

## 1. Table structure

```sql
CREATE TABLE `wl_meta_tags` (
  `meta_id`          int NOT NULL AUTO_INCREMENT,
  `is_fixed`         enum('Y','N') NOT NULL DEFAULT 'N',
  `entity_type`      varchar(80) DEFAULT NULL COMMENT 'name of controllers',
  `entity_id`        int NOT NULL DEFAULT '0',
  `page_url`         varchar(200) NOT NULL,
  `meta_title`       varchar(450) NOT NULL,
  `meta_description` varchar(650) NOT NULL,
  `meta_keyword`     varchar(450) DEFAULT NULL,
  `status`           enum('0','1') NOT NULL DEFAULT '1',
  PRIMARY KEY (`meta_id`),
  UNIQUE KEY `uq_meta_page_url` (`page_url`),
  KEY `entity_type` (`entity_type`),
  KEY `entity_id` (`entity_id`),
  KEY `idx_meta_entity` (`entity_type`, `entity_id`)
);
```

The unique key on `page_url` and the `idx_meta_entity` index were added by the
migration `2026-10-31-100000_UniqueMetaPageUrl`.

| Column | Meaning |
|---|---|
| `meta_id` | Row id |
| `is_fixed` | `Y` = part of the site structure (module pages, built-in pages); `N` = a record's row (blog post, added static page) or SEO-only row |
| `entity_type` | **What handles the address** (see §2). Empty/NULL = SEO data only, no route |
| `entity_id` | Id of the record shown (static page `page_id`, blog `blog_id`); `0` for module pages |
| `page_url` | **The address**, without leading/trailing slash, e.g. `teams`, `blogs/my-post`, `our-team/jane-tan`. The home page is `home` |
| `meta_title`, `meta_description`, `meta_keyword` | SEO tags; an empty value falls back to the page's default, then to Admin Settings |
| `status` | `1` = SEO data used; `0` = SEO data ignored. **The route works in both cases**, as in the reference |

## 2. `entity_type` conventions

One rule for every routed row, as in the CI3 reference:

```
entity_type '<module>/<method>[/<description>]'   entity_id 0  =>  Modules\<Module>\Controllers\<Module>::<method>()
entity_type '<module>/<method>[/<description>]'   entity_id N  =>  Modules\<Module>\Controllers\<Module>::<method>(N)
```

- `entity_id 0` is a **module page** (a landing page, a listing).
- `entity_id N` is a **record page**: the method receives the record's id.
- Anything after `<module>/<method>/` only describes the row. Static pages use it for their address.

| `entity_type` | `entity_id` | `page_url` | Handled by | Created by |
|---|---|---|---|---|
| `teams/index` (any `<module>/index`) | `0` | `teams` | `Teams::index()` | `make:module`, `make:module-route` |
| `home/index` | `0` | `home` (also serves `/`) | `Home::index()` | `PageMetaTagsSeeder` |
| `blog/index`, `blog/categories` | `0` | `blogs`, `blogs/categories` | `Blog::index()`, `Blog::categories()` | `PageMetaTagsSeeder` |
| `testimonials/index` | `0` | `testimonials` | `Testimonials::index()` | `PageMetaTagsSeeder` |
| `blog/detail` (`Blog::META_TYPE`) | `blog_id` | `blogs/<post slug>` | `Blog::detail(blog_id)` | Sitepanel → Blogs (add / edit / delete) |
| `teams/detail` (`Teams::META_TYPE`, generated) | record id | e.g. `teams/jane-tan` | `Teams::detail(id)` | the module, with `MetaModel::save_entity_page()` |
| `pages/index/<page_url>` e.g. `pages/index/aboutus` | `page_id` | `aboutus` | `Pages::index(page_id)` | Sitepanel → Manage Static Pages |
| empty / NULL | `0` | any | — (SEO data only) | Sitepanel → Meta Tags → Add |

Naming rules:

- `<module>` is the module folder (snake_case), and `<method>` a public method of its controller. Both use small letters, digits and underscores.
- A module keeps its own record type as a constant of its controller (`Blog::META_TYPE = 'blog/detail'`). `MetaModel` holds no module-specific types or routes.
- `MetaModel::entity_type($module, $method)` builds the string, e.g. `entity_type('teams')` → `teams/index`.

## 3. `page_url` conventions

- Small letters, digits and single hyphens per segment: `contact-form`, `our-team/jane-tan`.
- No leading or trailing `/`, no file extension, no query string.
- Unique across the whole table, enforced by the unique key.
- A module's default address is its name with `_` replaced by `-` (`contact_form` → `contact-form`).
- Reserved first segments: `sitepanel`, `uploads`, `uploaded_files`, `assets`, `html`, `home`, `index.php` (`Staticpages::RESERVED_URLS`).
- An address that is already a route in a route file (e.g. `uploads/...`) cannot be registered.

## 4. How a request is resolved

Code: `app/Config/ModuleRoutes.php` (loaded after `app/Config/Routes.php`) and
`Modules\Sitepanel\Models\MetaModel` (`modules/sitepanel/Models/MetaModel.php`).

```
GET /blogs/my-first-post
 1. ModuleRoutes.php normalises the path: "blogs/my-first-post" ("/" → "home").
 2. Skipped for sitepanel/*, uploads/*, uploaded_files/*, CLI, and paths already declared in a route file.
 3. MetaModel::get_route("blogs/my-first-post")   SELECT page_url, entity_type, entity_id
                                                  FROM wl_meta_tags WHERE page_url = ?   (one query)
      → entity_type "blog/detail", entity_id 19
 4. MetaModel::route_for($row)
      handler = module_route("blog/detail") = \Modules\Blog\Controllers\Blog::detail
      entity_id 19 > 0 → "\Modules\Blog\Controllers\Blog::detail/19"
 5. MetaModel::module_route("<module>/<method>[/...]")
      module "blog" → StudlyCase "Blog" → class \Modules\Blog\Controllers\Blog
      accepted only if: the class exists (namespace in app/Config/Autoload.php),
      it is a controller, and "detail" is a public, non-static method declared in
      the module (not initController(), not a "_" method). "sitepanel" is never
      resolved this way.
 6. $routes->match(['GET','POST'], 'blogs/my-first-post', '\Modules\Blog\Controllers\Blog::detail/19')
 7. Blog::detail(19) loads post 19 (404 if it is not public) → view → HTTP 200.
No row, or a row whose module/method doesn't exist → 404 override → Pages::not_found (designed 404 page).
```

The same steps serve every page:

| Request | Row | Handler |
|---|---|---|
| `/` | `home/index`, 0 | `Home::index()` |
| `/teams` | `teams/index`, 0 | `Teams::index()` |
| `/aboutus` | `pages/index/aboutus`, 2 | `Pages::index(2)` |
| `/teams/jane-tan` | `teams/detail`, 7 | `Teams::detail(7)` |

All routed pages accept GET and POST (`MetaModel::ROUTE_METHODS`). POST carries their
forms (blog comment, contact form, testimonial); pages without a form ignore it,
and the CSRF filter checks every POST.

Equivalent in the CI3 reference (`apps/core/MY_Router.php`):

```php
$tmp = $db->query("SELECT page_url, entity_type, entity_id FROM wl_meta_tags WHERE page_url = '$uri'")->result();
$routes[$val->page_url] = $val->entity_type . '/' . $val->entity_id;   // → module/controller/method/id
```

## 5. How SEO metadata is loaded

Code: `App\Libraries\PageMeta` (`service('pageMeta')`), rendered by `seo_head()` in
`app/Views/top_application.php`.

The controller passes the page's defaults:

```php
return $this->render('Modules\Teams\Views\teams', [
    'page_meta' => ['title' => 'Teams', 'description' => 'Meet our team.'],
]);
```

`PageMeta::head($page_meta)` picks the row:

- `'entity' => [type, id]` given → the **active** row with that `entity_type` and `entity_id`. Static pages pass `['pages/index/<url>', page_id]`; blog posts pass `[Blog::META_TYPE, blog_id]` (`blog/detail`); generated record pages pass `[<Module>::META_TYPE, id]`.
- Otherwise → the **active** row whose `page_url` is the current path (`/` and `/home` → `home`). This path lookup only matches single-segment addresses (`teams`, `aboutus`). A page at a nested address (e.g. `blogs/categories`) must pass `'entity'` to use its row.
- `'lookup' => false` → no row (404 and maintenance pages).

Each value is the first non-empty one of: the row → the controller default → Admin
Settings (`meta_title` / `meta_description` / `meta_keywords`) → site title.

Output (`app/Views/includes/seo_head.php`):

- `<title>`, meta description and keywords;
- the canonical URL (built from the path);
- `og:type`, `og:site_name`, `og:title`, `og:description`, `og:url`, and `og:image` (the OG image from Admin Settings).

Known limitation: `Blog::categories` (`/blogs/categories`) passes no `'entity'`, so the
SEO fields of its `blog/categories` row are not used yet. The page shows the
controller's defaults.

## 6. Static pages (`wl_cms_pages`)

```sql
wl_cms_pages: page_id, parent_id, page_name, friendly_url, layout, page_description,
              page_short_description, image, status ('0' inactive, '1' active, '2' deleted), page_updated_date
```

- Each page's address and SEO data are its own `wl_meta_tags` row:
  - `entity_type` = `pages/index/<friendly_url>`
  - `entity_id` = `page_id`
  - `page_url` = `friendly_url`
- `Pages::index($page_id)` shows a page only while its `status` is `'1'`; otherwise it returns 404.
- `layout` NULL → the page text (`cms_page.php`).
- `layout` set → a designed view (`Pages::DESIGNED_VIEWS`: `about_us`, `contact_us`, `sitemap`, `privacy_policy`, `terms`, `team_list`, ...). These built-in pages are created by `PageMetaTagsSeeder`, can be deactivated but not deleted, and their layout cannot be changed from the form.

### Create, update, delete

All page writes go through `PagesModel` (`modules/pages/Models/PagesModel.php`), in **one transaction** with the meta row:

| Action (Sitepanel → Manage Static Pages) | `wl_cms_pages` | `wl_meta_tags` |
|---|---|---|
| Add | insert (status `1`) | insert `pages/index/<url>`, `is_fixed` N; empty SEO fields filled from the page (`PagesModel::meta_for()`) |
| Edit | update | update the page's row; a changed address moves `page_url` **and** renames `entity_type` |
| Activate / Deactivate | `status` 1 / 0 | unchanged (an inactive page returns 404 and leaves the header menu) |
| Delete | `status` = `2` (soft delete) | row deleted (`PagesModel::bulk_delete()`) |

`PagesModel::save_page()` throws (and rolls back) if the address is held by another row.

### Other records

| Record | Row maintained by |
|---|---|
| Blog post | `Blogs` controller → `MetaModel::save_entity_meta(Blog::META_TYPE, $id, 'blogs/<slug>', $seo)`; empty SEO fields are filled from the post; deleting the post deletes the row |
| Any module's record | `MetaModel::save_entity_page($type, $id, $page_url, $meta, $defaults)`, one transaction; refused when another row holds the address; `delete_entity_meta($type, $id)` when the record is deleted |
| Module page | `MetaModel::register_module_page()` (generator commands), one transaction |
| SEO-only row | Sitepanel → Meta Tags → Add (single-segment slug, no `entity_type`) |

Sitepanel → Meta Tags can edit the SEO fields of every row.

- It cannot change a row's `page_url` or `entity_type`.
- It refuses to delete a row that is a route (`MetaModel::is_route()`): static pages, blog posts and module pages. Remove those through their own module, or in the database when a module itself is removed.

## 7. Duplicate protection

| Layer | Check |
|---|---|
| Database | `UNIQUE (page_url)` — two rows can never share an address |
| Static pages | `Staticpages::url_is_free()`: reserved first segment, existing route, another page, another `wl_meta_tags` row |
| Generator commands | reserved address, existing route, existing static page, existing `page_url`, existing `<module>/index` row; checked again inside the insert transaction |
| Meta Tags (Sitepanel) | `MetaModel::check_page_url()` before adding an SEO-only row |
| Seeder | `PageMetaTagsSeeder` reuses existing rows and reports addresses held by something else |

## 8. Registering pages of a module

**Module page** (`entity_id` 0): `php spark make:module-route <module>` registers
`<module>/index`. For another landing page, e.g. `/teams/alumni` handled by
`Teams::alumni()`, add the method and insert its row from a seeder or SQL:

```sql
INSERT INTO wl_meta_tags (is_fixed, entity_type, entity_id, page_url, meta_title, meta_description, meta_keyword, status)
VALUES ('Y', 'teams/alumni', 0, 'teams/alumni', 'Alumni', '', 'Alumni', '1');
```

**Record page** (one per record, e.g. a team member). `make:module` already
generates `Teams::detail($id)`, `Teams::META_TYPE = 'teams/detail'` and
`Views/teams_detail.php`. The code that adds or edits a record saves its address:

```php
$error = (new MetaModel())->save_entity_page(Teams::META_TYPE, $team_id, 'teams/' . $slug,
    ['meta_title' => $post('meta_title'), 'meta_description' => $post('meta_description'), 'meta_keyword' => $post('meta_keyword')],
    ['meta_title' => $name, 'meta_description' => $designation, 'meta_keyword' => $name]);   // defaults for empty fields
if ($error !== null) { /* show it: e.g. the address is used by another page */ }
```

Deleting the record deletes its row: `(new MetaModel())->delete_entity_meta(Teams::META_TYPE, $team_id);`

`/teams/<slug>` then reaches `Teams::detail(<team_id>)` with no change to `MetaModel` or any route file.
Record pages pass `'entity' => [Teams::META_TYPE, $id]` in `$page_meta`, so their row's SEO data is used (§5).

**Before inserting by hand**, check that the address is free:

```sql
SELECT * FROM wl_meta_tags WHERE page_url = 'teams/alumni';
```

## 8a. Upgrading existing data (blog posts)

Before this convention, blog posts' rows had `entity_type` `blog` and a blog-only route.
The migration `2026-10-31-120000_BlogMetaEntityType` (`php spark migrate`) changes
them in place to `blog/detail`.

- **Kept:** `meta_id`, `entity_id` (blog_id), `page_url` and the SEO data, so no address changes and no row is created or deleted.
- **Rollback:** `down()` restores `blog`.
- **Until it runs:** old `blog` rows don't resolve (404), so run `php spark migrate` when deploying this change.

## 9. Seeding

`php spark db:seed PageMetaTagsSeeder`, also run by `DatabaseSeeder`, creates:

- the built-in static pages and their rows;
- the module rows (home, blogs, blog categories, testimonials);
- a row for each blog post and static page that has none.

It is idempotent: running it again changes nothing that already exists.
