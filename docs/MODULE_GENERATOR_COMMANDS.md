# Module Generator Commands

Two Spark commands create website modules that follow the project's MVC layout and
register their address in `wl_meta_tags`:

| Command | Implementation | Use |
|---|---|---|
| `php spark make:module <name> [--url <address>] [--no-status]` | `app/Commands/MakeModule.php` (`App\Commands\MakeModule`) | Generate a new module |
| `php spark make:module-route <name> [--url <address>]` | `app/Commands/MakeModuleRoute.php` (`App\Commands\MakeModuleRoute`, extends `MakeModule`) | Check and register a module you copied |

Run them from the project root (`D:\wamp64\www\customcart`). They need the
database, since they write to `wl_meta_tags`. `php spark help make:module` prints the usage.

See also: [DYNAMIC_ROUTING_AND_SEO.md](DYNAMIC_ROUTING_AND_SEO.md),
[MVC_DEVELOPMENT_GUIDE.md](MVC_DEVELOPMENT_GUIDE.md).

## 1. Names

| Input | Folder (snake_case) | Namespace / controller (StudlyCase) | Model | View | Address | `entity_type` |
|---|---|---|---|---|---|---|
| `teams` or `Teams` | `modules/teams` | `Modules\Teams`, `Teams` | `TeamsModel` | `teams.php` | `teams` | `teams/index` |
| `contact_form` or `ContactForm` | `modules/contact_form` | `Modules\ContactForm`, `ContactForm` | `ContactFormModel` | `contact_form.php` | `contact-form` | `contact_form/index` |
| `teams --url our-teams` | `modules/teams` | `Modules\Teams` | `TeamsModel` | `teams.php` | `our-teams` | `teams/index` |

Rules (`MakeModule::names()`, `MakeModule::module()`):

- **Name:** letters, digits and underscores, starting with a letter. No double, leading or trailing underscores. At most 40 characters in snake_case.
- **Refused names:**
  - PHP reserved words (`class`, `list`, ...);
  - Windows device names (`con`, `nul`, ...);
  - `sitepanel`, `pages`, `app`, `config`.
- **Address (`--url`):** small letters, digits and single hyphens (`our-teams`), one segment. `--url our-teams` and `--url=our-teams` both work.
- **Model names (`MakeModule::item_names()`):** singular for one row and plural for lists, as in the existing models:

  | Module | One row | List / bulk / key |
  |---|---|---|
  | `teams` | `get_team_by_id()` | `get_teams()`, `get_teams_by_ids()`, `team_id` |
  | `brochure` | `get_brochure_by_id()` | `get_brochures()`, `get_brochures_by_ids()`, `brochure_id` |
  | `category` | `get_category_by_id()` | `get_categories()`, `get_categories_by_ids()`, `category_id` |

  `TABLE` is `wl_<name>` as typed.
- **`--no-status`:** for a table with no active / inactive column (like the enquiries). The model gets no status filter, no `get_active_*()` and no `bulk_update_status()`.

The folder → class mapping is the same one the router uses (`MetaModel::studly()`),
so a generated or correctly renamed module is always resolvable.

## 2. `php spark make:module teams`

### What it does, in order

1. Validates the name and address.
2. Refuses, before touching anything, if any of these exists:
   - a folder in `modules/` with the same name (case and underscores ignored);
   - the namespace `Modules\Teams` (or a namespace pointing at `modules/teams`) in `app/Config/Autoload.php`;
   - a reserved address (`sitepanel`, `uploads`, `uploaded_files`, `assets`, `html`, `home`, `index.php`);
   - an address used by a route in a route file;
   - a `wl_meta_tags` row with that `page_url`;
   - a `wl_meta_tags` row with `entity_type` `teams/index`;
   - a static page (`wl_cms_pages`, not deleted) with that address.
3. Creates the files from the templates in `MakeModule` (`CONTROLLER_TEMPLATE`, `MODEL_TEMPLATE`, `VIEW_TEMPLATE`). Each file is checked to parse as PHP and opened with mode `x`, so an existing file is never overwritten.
4. Adds `'Modules\Teams' => ROOTPATH . 'modules/teams',` after the last module line of `$psr4` in `app/Config/Autoload.php`, aligned with its neighbours.
5. Inserts the `wl_meta_tags` row in a transaction (`MetaModel::register_module_page()`), which re-checks for duplicates.
6. If any step fails, it deletes the files and folders it created and restores `Autoload.php`. The database row is the last step, so no row is left behind either.

### Files created

```
modules/teams/
├── Controllers/Teams.php
├── Models/TeamsModel.php
├── Views/teams.php
└── Views/teams_detail.php
```

`Controllers/Teams.php`:

```php
namespace Modules\Teams\Controllers;

use App\Controllers\PublicController;
use Modules\Teams\Models\TeamsModel;

class Teams extends PublicController
{
    protected TeamsModel $teams_model;

    protected function init(): void
    {
        $this->teams_model = new TeamsModel();
    }

    public function index(): string
    {
        return $this->render('Modules\Teams\Views\teams', [
            'page_meta'     => ['title' => 'Teams', 'description' => ''],
            'heading_title' => 'Teams',
        ]);
    }
}
```

`Controllers/Teams.php` also has `detail($id)` and `META_TYPE = 'teams/detail'`: one record's page.
The router calls `detail()` with the `entity_id` of the record's `wl_meta_tags` row, which the module saves with
`MetaModel::save_entity_page()` (see [DYNAMIC_ROUTING_AND_SEO.md §8](DYNAMIC_ROUTING_AND_SEO.md#8-registering-pages-of-a-module)).
`detail()` returns 404 when the record doesn't exist or is inactive. `Views/teams_detail.php` is its page.

`Models/TeamsModel.php`: `class TeamsModel extends BaseModel`, in the standard model structure
(reference `modules/brochure/Models/BrochureModel.php`, see
[PROJECT_ARCHITECTURE.md §7](PROJECT_ARCHITECTURE.md#7-models)).
It works on `TABLE = 'wl_teams'` and `PRIMARY_KEY = 'team_id'`, and assumes a `title` and a `status` ('1'/'0') column. Adjust these to the real table once its migration exists.

| Method | Returns |
|---|---|
| `get_teams($offset, $limit, $keyword, $status)` | one Sitepanel list page (title search, status filter, newest first); total in `$total_rec_found` |
| `get_active_teams($limit)` | the active rows for the website |
| `get_team_by_id($id)` | one row, `null` if none |
| `get_teams_by_ids($ids)` | rows for a bulk action |
| `bulk_update_status($ids, '1'\|'0')`, `bulk_delete($ids)` | `bool` (used by `AdminController::process_bulk_action()`) |

With `--no-status`: `get_teams($offset, $limit, $keyword)`, `get_team_by_id()`, `get_teams_by_ids()` and `bulk_delete()` only.

The controller's `index()` has a commented example of loading `get_active_teams()`; it is left commented so the page works before the table exists.

`Views/teams.php`: the site layout (`top_application` / `bottom_application`), a breadcrumb and an `<h1>`.

### Row inserted

| Column | Value |
|---|---|
| `entity_type` | `teams/index` |
| `entity_id` | `0` |
| `page_url` | `teams` |
| `is_fixed` | `Y` |
| `meta_title` | `Teams - <site name from Admin Settings>` |
| `meta_description` | empty (the controller default / Admin Settings are used) |
| `meta_keyword` | `Teams` |
| `status` | `1` |

### Output

```
$ php spark make:module teams
Module teams created successfully.
  Created: modules/teams/Controllers/Teams.php
  Created: modules/teams/Models/TeamsModel.php
  Created: modules/teams/Views/teams.php
  Updated: app/Config/Autoload.php (namespace Modules\Teams)
MVC structure generated successfully.
Frontend route registered successfully. (wl_meta_tags: entity_type teams/index, page_url teams)
URL: http://localhost/customcart/teams
SEO: Sitepanel > Meta Tags (teams)
```

The URL is printed with the `app.baseURL` of `.env`. The page answers on every
host that serves the application (e.g. `http://localhost:8081/teams`).

### Errors (nothing is changed)

```
$ php spark make:module teams
Error: Module folder modules/teams already exists. To register a module you copied, run: php spark make:module-route teams

$ php spark make:module news --url testimonials
Error: The address "testimonials" is already registered in wl_meta_tags (meta_id 4, entity_type "testimonials/index"). Choose another one with --url <address>.

$ php spark make:module news --url sitepanel
Error: The address "sitepanel" is reserved. Choose another one with --url <address>.

$ php spark make:module teams --force
Error: --force is not supported: this command never overwrites a module. ...

$ php spark make:module class
Error: "class" is a reserved word and cannot be used as a module name.
```

## 3. `php spark make:module-route teams`

Use it after **copying** an existing working module and renaming it. It checks the
module, then registers it exactly as `make:module` would. It never creates or
changes module files.

### Checks (all before anything is written)

1. `modules/teams/` exists, with `Controllers/`, `Models/` and `Views/`.
2. `modules/teams/Controllers/Teams.php` exists (the controller is named after the module).
3. Every `.php` file in the module parses (a syntax error names the file and line).
4. Every class file outside `Views/` and `Config/` declares the namespace of its folder: `Controllers/Teams.php` → `Modules\Teams\Controllers`, `Models/TeamsModel.php` → `Modules\Teams\Models`.
5. The address and `teams/index` are free (same checks as `make:module`).
6. The class `Modules\Teams\Controllers\Teams` loads, extends `App\Controllers\PublicController`, and has a public `index()` resolvable by the router (`MetaModel::module_route()`).

Warnings (not errors) list files that still refer to another module's namespace
(e.g. `Modules\Testimonials\...`). Such a reference is usually a missed rename, but may be intentional, e.g. reusing a shared model.
References to `Modules\Sitepanel` are not reported.

### Then

- Adds `'Modules\Teams' => ROOTPATH . 'modules/teams',` to `app/Config/Autoload.php` if it is missing.
- Inserts the `wl_meta_tags` row (`teams/index`, `teams`) in a transaction.
- On failure, restores `Autoload.php`; no row is inserted.

### Example: copying `testimonials` to `reviews`

```
$ php spark make:module-route reviews        # folder copied, nothing renamed yet
Error: modules/reviews/Controllers/Reviews.php is missing (the module controller is named after the module).

$ php spark make:module-route reviews        # file renamed, namespace not
Error: modules/reviews/Controllers/Reviews.php declares namespace Modules\Testimonials\Controllers; it must be Modules\Reviews\Controllers.

$ php spark make:module-route reviews        # namespace fixed, class still "Testimonials"
Error: Class Modules\Reviews\Controllers\Reviews was not found in modules/reviews/Controllers/Reviews.php (check its class name and namespace). Nothing was changed.

$ php spark make:module-route reviews        # complete
Module reviews checked successfully. (Modules\Reviews\Controllers\Reviews::index)
  Updated: app/Config/Autoload.php (namespace Modules\Reviews)
Frontend route registered successfully. (wl_meta_tags: entity_type reviews/index, page_url reviews)
URL: http://localhost/customcart/reviews
SEO: Sitepanel > Meta Tags (reviews)

$ php spark make:module-route reviews        # again
Error: The address "reviews" is already registered in wl_meta_tags (meta_id 10, entity_type "reviews/index"). ...
```

## 4. Removing a module

There is no remove command (it would delete code). By hand:

1. Delete `modules/<name>/`.
2. Delete its line in `app/Config/Autoload.php`.
3. Delete its row in the database (Sitepanel → Meta Tags refuses to delete route rows):

```sql
DELETE FROM wl_meta_tags WHERE entity_type = '<name>/index' AND entity_id = 0;
```

If the folder or namespace line is removed but the row stays, the address returns
404 (the router never routes to a missing class).

## 5. Adapting the commands

- Templates are class constants of `MakeModule` (`CONTROLLER_TEMPLATE`, `MODEL_TEMPLATE`, `VIEW_TEMPLATE`, tokens `{snake}`, `{Studly}`, `{Title}`, `{url}`). Edit them to change what new modules start with; generated files are checked to parse before they are written.
- Registration is `MetaModel::register_module_page($type, $page_url, $meta)`. It returns `null` on success, or the reason it refused.
- In another CodeIgniter 4 project, keep the safety rules:
  - validate the input;
  - check for conflicts before writing;
  - open files with mode `x`, so nothing is overwritten;
  - make the database write the last step;
  - roll back on failure.

  Rewrite the templates, the registry query and the namespace registration for that project's structure.
- Spark commands do not exist in CodeIgniter 3. There, a module is copied by hand and its `wl_meta_tags` row inserted with SQL (see [PROJECT_ARCHITECTURE.md §15](PROJECT_ARCHITECTURE.md#15-project-specific-vs-reusable)).
