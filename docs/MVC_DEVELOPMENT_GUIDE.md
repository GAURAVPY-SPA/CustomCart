# MVC Development Guide

How to add a feature to this project without breaking its structure. The worked
example is a `teams` module:

- a public page at `/teams` listing team members;
- a Sitepanel section **Manage Teams** to add, edit, activate/deactivate and delete them.

Read first: [PROJECT_ARCHITECTURE.md](PROJECT_ARCHITECTURE.md) (folders, base classes),
[DYNAMIC_ROUTING_AND_SEO.md](DYNAMIC_ROUTING_AND_SEO.md) (addresses and SEO).

## 1. Conventions at a glance

| Item | Convention | Example |
|---|---|---|
| Module folder | `modules/<snake_case>` | `modules/teams` |
| Namespace | `Modules\<StudlyCase>` (registered in `app/Config/Autoload.php`) | `Modules\Teams` |
| Website controller | `modules/<m>/Controllers/<Studly>.php`, extends `App\Controllers\PublicController` | `Teams` |
| Model | `<Name>Model`, extends `App\Models\BaseModel`, with `TABLE` / `PRIMARY_KEY` constants | `TeamsModel` |
| Website view | `modules/<m>/Views/<snake>.php`, includes the site layout | `teams.php` |
| Address | a `wl_meta_tags` row: `entity_type` `<module>/<method>`, `page_url` `<address>` | `teams/index`, `teams` |
| Sitepanel controller | `modules/sitepanel/Controllers/<Studly>.php`, extends `App\Controllers\AdminController` | `Teams` |
| Sitepanel views | `modules/sitepanel/Views/<snake>/view_<item>_list.php`, `view_<item>_form.php` | `teams/view_team_list.php` |
| Sitepanel URL | `sitepanel/<snake>/<action>/<id>`, declared in `modules/sitepanel/Config/Routes.php` | `sitepanel/teams/edit/3` |
| Permission row | `tbl_admin_sections.section_controller` = snake_case of the controller class | `teams` |
| Table | `wl_<plural>`, primary key `<singular>_id`, `status enum('0','1')` | `wl_teams`, `team_id` |
| Methods | snake_case (`get_teams()`, `get_team_by_id()`), properties snake_case (`$teams_model`) | |
| Model results | `false` = database error (logged), `null` = not found, else data | |

Rules that keep the architecture intact:

- **Controllers stay thin.** They read input, validate, call models and choose a view. No SQL in controllers or views.
- **Initialise in `init()`.** Models and libraries are created there, never in a constructor.
- **Website views include the layout themselves.** `echo view('top_application')` ... `echo view('bottom_application')`.
- **Escape all output.** Echo every value with `html_escape()`; editor HTML goes through `HtmlSanitizer::clean()`.
- **Build URLs with helpers.** Use `site_url()`, `base_url()`, `asset_url()`, `theme_url()`; never a hard-coded host or path.
- **Register addresses, not routes.** A website address is a `wl_meta_tags` row, never a line in a route file. The Sitepanel is the exception: its routes are declared in `modules/sitepanel/Config/Routes.php`.
- **Change the schema with migrations.** Required rows go in seeders (written to be safe to run again). Never edit the SQL dump.
- **Upload with `ImageUpload`.** Use only folders declared in `app/Config/Uploads.php`.

## 2. Create the module

Either generate it:

```bash
php spark make:module teams
```

or copy a working module, rename it, and register it with
`php spark make:module-route teams`. Both are covered in
[MODULE_GENERATOR_COMMANDS.md](MODULE_GENERATOR_COMMANDS.md).

Either way the result is:

```
modules/teams/Controllers/Teams.php
modules/teams/Models/TeamsModel.php
modules/teams/Views/teams.php
app/Config/Autoload.php      + 'Modules\Teams' => ROOTPATH . 'modules/teams',
wl_meta_tags                 + entity_type 'teams/index', page_url 'teams'
```

`http://localhost:8081/teams` already returns 200 with the placeholder view.

## 3. Database: migration

`app/Database/Migrations/2026-11-01-100000_CreateTeamsTable.php`. Use the current
date and time in the file name.

```php
<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Team members: Sitepanel > Manage Teams, shown on /teams.
 */
class CreateTeamsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'team_id'     => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'name'        => ['type' => 'VARCHAR', 'constraint' => 100],
            'designation' => ['type' => 'VARCHAR', 'constraint' => 100],
            'photo'       => ['type' => 'VARCHAR', 'constraint' => 100, 'default' => ''],
            'sort_order'  => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'status'      => ['type' => 'ENUM', 'constraint' => ['0', '1'], 'default' => '1', 'comment' => '1=Active,0=Inactive'],
            'date_added'  => ['type' => 'DATETIME'],
        ]);
        $this->forge->addPrimaryKey('team_id');
        $this->forge->addKey(['status', 'sort_order'], false, false, 'idx_teams_status_order');
        $this->forge->createTable('wl_teams', true, ['ENGINE' => 'InnoDB', 'COLLATE' => 'utf8mb4_unicode_ci']);
    }

    public function down(): void
    {
        $this->forge->dropTable('wl_teams', true);
    }
}
```

```bash
php spark migrate
```

If the module stores images, add a folder to `app/Config/Uploads.php`:

```php
'teams' => [['wl_teams', 'photo']],
```

`ImageUpload` then accepts the folder, and only deletes a file once no row uses it.

## 4. Model

`modules/teams/Models/TeamsModel.php`, in the standard model structure (reference:
`modules/brochure/Models/BrochureModel.php`; see
[PROJECT_ARCHITECTURE.md §7](PROJECT_ARCHITECTURE.md#7-models)). It is used by the website
and by the Sitepanel (one model per table; the Sitepanel may import a website model).

```php
<?php

namespace Modules\Teams\Models;

use App\Models\BaseModel;

/**
 * Team members (TeamsModel::TABLE) — the website's /teams page and the
 * Sitepanel list, as the other modules' models (BrochureModel, FaqModel).
 *
 * Schema (docs/MVC_DEVELOPMENT_GUIDE.md, migration CreateTeamsTable):
 * team_id, name, designation, photo, sort_order, status '1' / '0', date_added.
 * The table is not created yet: run its migration before using these
 * methods (Teams::index doesn't call them yet, so /teams keeps working).
 *
 * BaseModel returns FALSE when the database is unavailable or a query
 * fails (logged), NULL when no row matches. Other helpers: findCount(),
 * safe_insert(), safe_update(), safe_delete().
 */
class TeamsModel extends BaseModel
{
    public const TABLE       = 'wl_teams';
    public const PRIMARY_KEY = 'team_id';

    /**
     * One Sitepanel list page, in display order then newest first; the total
     * is left in $this->total_rec_found.
     *
     * @param string $keyword Part of the name ('' = all)
     * @param string $status  '1' | '0' | '' (all)
     *
     * @return array|false
     */
    public function get_teams(int $offset = 0, int $limit = 20, string $keyword = '', string $status = '')
    {
        return $this->paged_rows(self::TABLE, 'sort_order ASC, ' . self::PRIMARY_KEY . ' DESC', $offset, $limit, static function ($db) use ($keyword, $status) {
            $keyword !== '' && $db->like('name', $keyword);
            $status !== '' && $db->where('status', $status);
        });
    }

    /**
     * Active members for the website, in display order.
     *
     * @return array|false
     */
    public function get_active_teams(int $limit = 100)
    {
        if (! $this->db_ready()) {
            return false;
        }

        return $this->all_rows($this->table(self::TABLE)
            ->select(self::PRIMARY_KEY . ', name, designation, photo')
            ->where('status', '1')
            ->orderBy('sort_order', 'ASC')
            ->orderBy(self::PRIMARY_KEY, 'ASC')
            ->limit($limit)
            ->get());
    }

    /**
     * @param int|string $id
     *
     * @return array|false|null
     */
    public function get_team_by_id($id)
    {
        return $this->find(self::TABLE, [self::PRIMARY_KEY => (int) $id]);
    }

    /**
     * Rows for a Sitepanel bulk action (AdminController::process_bulk_action()).
     *
     * @param list<int> $ids
     *
     * @return array|false
     */
    public function get_teams_by_ids(array $ids)
    {
        return $this->find_many(self::TABLE, self::PRIMARY_KEY, $ids);
    }

    /**
     * @param list<int> $ids
     */
    public function bulk_update_status(array $ids, string $status): bool
    {
        return $this->safe_update_many(self::TABLE, self::PRIMARY_KEY, $ids, ['status' => $status]);
    }

    /**
     * @param list<int> $ids
     */
    public function bulk_delete(array $ids): bool
    {
        return $this->safe_delete_many(self::TABLE, self::PRIMARY_KEY, $ids);
    }
}
```

`AdminController::process_bulk_action()` calls exactly these three methods: the
`get_<items>_by_ids()` method whose name you pass it, `bulk_update_status($ids, '1'|'0')`
and `bulk_delete($ids)`. Keep those names, as `FaqModel` and `CouponModel` do.

## 5. Website controller (controller → model → view)

`modules/teams/Controllers/Teams.php`:

```php
<?php

namespace Modules\Teams\Controllers;

use App\Controllers\PublicController;
use Modules\Teams\Models\TeamsModel;

/**
 * /teams: the active team members.
 *
 * Routed through its wl_meta_tags row (entity_type teams/index, page_url teams);
 * SEO data is edited in Sitepanel > Meta Tags, the values below are the defaults.
 */
class Teams extends PublicController
{
    protected TeamsModel $teams_model;

    protected function init(): void
    {
        $this->teams_model = new TeamsModel();
    }

    public function index(): string
    {
        $rows = $this->teams_model->get_active_teams();
        if ($rows === false) {
            log_message('error', 'Teams::index could not load team members.');
        }

        return $this->render('Modules\Teams\Views\teams', [
            'page_meta'     => ['title' => 'Our Team', 'description' => 'Meet the people behind our work.'],
            'heading_title' => 'Our Team',
            'teams'         => is_array($rows) ? $rows : [],
        ]);
    }
}
```

- A database failure shows an empty list, not an error page. Failures are logged in `writable/logs/`.
- Use `$this->not_found_page()` when a requested record does not exist (designed 404 page).
- `render()` merges the shared data: `site_name`, `site_logo`, `copyright`, and so on.

## 6. Website view

`modules/teams/Views/teams.php`:

```php
<?php
/* /teams (Teams::index). $teams = active members. */
echo view('top_application');
?>
<section class="section" data-screen-label="Our Team">
<div class="shell">
<nav class="page-breadcrumb" aria-label="Breadcrumb">
<ol class="breadcrumb mb-0">
<li class="breadcrumb-item"><a href="<?php echo site_url(); ?>">Home</a></li>
<li class="breadcrumb-item active" aria-current="page"><?php echo html_escape($heading_title); ?></li>
</ol>
</nav>
<h1 class="h2"><?php echo html_escape($heading_title); ?></h1>
<?php if (empty($teams)): ?>
<p>No team members yet.</p>
<?php else: ?>
<div class="row g-4">
<?php foreach ($teams as $member): ?>
<div class="col-md-4">
<?php if ($member['photo'] !== ''): ?>
<img src="<?php echo base_url(config('Uploads')->urlPrefix . 'teams/' . rawurlencode($member['photo'])); ?>" alt="<?php echo html_escape($member['name']); ?>" class="img-fluid" loading="lazy">
<?php endif; ?>
<h2 class="h5"><?php echo html_escape($member['name']); ?></h2>
<p><?php echo html_escape($member['designation']); ?></p>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>
</section>
<?php echo view('bottom_application'); ?>
```

Take the markup and classes from the design reference in `html/` (e.g.
`html/our-team.htm`). Theme images come from `theme_url().'images/...'`.

To show the page in the header menu, add it to `app/Config/SiteMenu.php`. A link
appears automatically once its address is registered.

## 7. Sitepanel integration

### 7.1 Controller

`modules/sitepanel/Controllers/Teams.php` (same pattern as `Faq.php`):

```php
<?php

namespace Modules\Sitepanel\Controllers;

use App\Controllers\AdminController;
use App\Libraries\FormValidation;
use App\Libraries\ImageUpload;
use Modules\Sitepanel\Libraries\AdminAccess;
use Modules\Teams\Models\TeamsModel;

/**
 * Sitepanel > Manage Teams (wl_teams).
 *
 *   index          teams/view_team_list
 *   add            teams/view_team_form
 *   edit/<id>      teams/view_team_form
 *   status/<id>    POST, AdminController::update_status()
 *   delete/<id>    POST, AdminController::delete_record()
 *   bulk           POST, activate / deactivate / delete the selected rows
 */
class Teams extends AdminController
{
    protected string $base           = 'sitepanel/teams';
    protected string $item           = 'Team member';
    protected string $menu_highlight = 'teams';

    protected TeamsModel $teams_model;

    protected function init(): void
    {
        $this->teams_model = new TeamsModel();
    }

    public function index(): string
    {
        $keyword = mb_substr(trim((string) $this->request->getGet('keyword')), 0, 80);
        $status  = in_array($this->request->getGet('status'), ['1', '0'], true) ? $this->request->getGet('status') : '';
        $page    = $this->current_page();
        $res     = $this->teams_model->get_teams(($page - 1) * self::PAGESIZE, self::PAGESIZE, $keyword, $status);

        return $this->render('teams/view_team_list', [
            'heading_title' => 'Manage Teams',
            'breadcrumbs'   => ['Teams' => null],
            'res'           => $res,
            'total'         => $this->teams_model->total_rec_found,
            'page'          => $page,
            'page_links'    => admin_pagination($this->base, $this->teams_model->total_rec_found, self::PAGESIZE),
            'keyword'       => $keyword,
            'status'        => $status,
        ]);
    }

    public function add(): string
    {
        $this->require_privilege(AdminAccess::PRIV_ADD);
        $errors = [];

        if ($this->request->getMethod() === 'POST') {
            $errors = $this->save(null);
            if ($errors === []) {
                $this->session->setFlashdata('sitepanel_success', 'Team member added.');
                redirect_now($this->base);
            }
        }

        return $this->render('teams/view_team_form', [
            'heading_title' => 'Add Team Member',
            'breadcrumbs'   => ['Teams' => site_url($this->base), 'Add Team Member' => null],
            'res'           => ['name' => '', 'designation' => '', 'photo' => ''],
            'errors'        => $errors,
        ]);
    }

    /**
     * @param int|string $id
     */
    public function edit($id = 0): string
    {
        $res = $this->record_or_back($this->teams_model->get_team_by_id($id));
        $this->require_privilege(AdminAccess::PRIV_EDIT);
        $errors = [];

        if ($this->request->getMethod() === 'POST') {
            $errors = $this->save($res);
            if ($errors === []) {
                $this->session->setFlashdata('sitepanel_success', 'Team member updated.');
                redirect_now($this->base);
            }
        }

        return $this->render('teams/view_team_form', [
            'heading_title' => 'Edit Team Member',
            'breadcrumbs'   => ['Teams' => site_url($this->base), 'Edit Team Member' => null],
            'res'           => $res,
            'errors'        => $errors,
        ]);
    }

    /** @param int|string $id */
    public function status($id = 0): void
    {
        $this->update_status('wl_teams', 'team_id', $id);
    }

    /** @param int|string $id */
    public function delete($id = 0): void
    {
        $this->delete_record('wl_teams', 'team_id', $id, 'photo', 'teams');
    }

    public function bulk(): void
    {
        $this->process_bulk_action($this->teams_model, 'get_teams_by_ids', 'Team member', [], 'photo', 'teams');
    }

    /**
     * Validate and save; returns the errors (empty = saved).
     *
     * @return list<string>
     */
    protected function save(?array $row): array
    {
        $v = new FormValidation($this->request, $this);
        $v->set_rules('name', 'Name', 'trim|required|max_length[100]');
        $v->set_rules('designation', 'Designation', 'trim|required|max_length[100]');
        if (! $v->run()) {
            return array_values($v->error_array());
        }

        $data = [
            'name'        => trim((string) $this->request->getPost('name')),
            'designation' => trim((string) $this->request->getPost('designation')),
        ];

        $upload = new ImageUpload();
        if ($upload->has_file('photo')) {
            $saved = $upload->save('photo', 'teams', 'Photo', [1200, 1200]);
            if (isset($saved['error'])) {
                return [$saved['error']];
            }
            $data['photo'] = $saved['file'];
        }

        $ok = $row
            ? $this->teams_model->safe_update('wl_teams', $data, ['team_id' => (int) $row['team_id']])
            : $this->teams_model->safe_insert('wl_teams', $data + ['status' => '1', 'date_added' => date('Y-m-d H:i:s')]) !== false;

        if ($ok && $row && isset($data['photo']) && $row['photo'] !== '') {
            $upload->delete('teams', $row['photo']); // the replaced photo
        }

        return $ok ? [] : ['Team member could not be saved. Please try again.'];
    }
}
```

Points to keep:

- **Privileges first.** `require_privilege()` runs before any write. `update_status()` and `delete_record()` also check POST and privileges themselves.
- **Unknown ids.** `record_or_back()` redirects back with "not found" for an unknown id.
- **Validation errors.** They come back as a list and are shown by `includes/form_errors`. Field values are kept with `set_value()`.
- **Custom rules.** Write them as named closures: `['unique_name', fn ($value) => ...]` plus a message.
- **`regex_match`.** Use the `/` delimiter (`regex_match[/^[a-z]+$/]`).

### 7.2 Routes

Add the section to `$sections` in `modules/sitepanel/Config/Routes.php`. Each action
is listed with whether it takes an id:

```php
'teams' => ['Teams', ['index' => false, 'add' => false, 'edit' => true, 'status' => true, 'delete' => true, 'bulk' => false]],
```

The group already applies the `sitepanelauth` filter.

### 7.3 Menu and permissions

Insert the section row so it appears in the sidebar and can be granted to sub-admins.
Do it in a seeder, following `OtherManagementSectionsSeeder`:

```php
$this->db->table('tbl_admin_sections')->insert([
    'parent_id'          => <id of the heading row, e.g. "Other Management">,
    'section_title'      => 'Manage Teams',
    'section_controller' => 'teams',          // snake_case of the class name
    'disp_order'         => 13,
    'actual_privilege'   => '1,2,3,4,5,6',    // view, add, edit, activate, deactivate, delete
    'status'             => '1',
]);
```

Optional: pick an icon in `sp_section_icon()` (`modules/sitepanel/Helpers/sitepanel_helper.php`),
e.g. `'teams' => 'users'`.

### 7.4 Views

Copy `modules/sitepanel/Views/faq/view_faq_list.php` and `view_faq_form.php` to
`modules/sitepanel/Views/teams/view_team_list.php` and `view_team_form.php`.
Then adapt the fields:

- **Page frame:** `sp_view('includes/header')` and `sp_view('includes/footer')`.
- **Errors:** `sp_view('includes/form_errors', ['errors' => $errors])`.
- **Forms:**
  - Open with `form_open($action)`, which adds the CSRF field.
  - A file upload needs `form_open_multipart($action)`.
- **Lists:** `includes/bulk_actions`, `includes/row_actions` and `includes/list_foot`. Show buttons according to `$addPrvg`, `$editPrvg`, `$activatePrvg`, `$deactivatePrvg` and `$deletePrvg`.
- **Status:** `<span class="sp-badge sp-badge--ok">Active</span>` / `sp-badge--muted` Inactive.
- **Text links:** `class="sp-link"`.

## 8. SEO and addresses

- **The page's address and SEO data** are its `wl_meta_tags` row, created by the command. Edit title, description and keywords in Sitepanel → Meta Tags.
- **Controller defaults** in `page_meta` are used only for fields left empty there.
- **A member's own page** (`/teams/<slug>`) is a record page.
  1. The generated `Teams::detail($id)` shows it.
  2. Its row is `entity_type` `Teams::META_TYPE` (`teams/detail`) with `entity_id` = `team_id`.
  3. In the Sitepanel controller's `save()`, after the record is saved, call `(new MetaModel())->save_entity_page(Teams::META_TYPE, $team_id, 'teams/' . $slug, $seo, $defaults)`.
  4. In `delete()`, call `delete_entity_meta(Teams::META_TYPE, $team_id)`.

  No change to `MetaModel` or any route file is needed. See
  [DYNAMIC_ROUTING_AND_SEO.md §8](DYNAMIC_ROUTING_AND_SEO.md#8-registering-pages-of-a-module).

## 9. Error handling summary

| Situation | Handling |
|---|---|
| Database down / query failed | Model returns `false` and logs; the controller shows an empty state or "could not be loaded" |
| Record not found (website) | `$this->not_found_page()` → designed 404 |
| Record not found (Sitepanel) | `record_or_back()` → previous page with a flash error |
| Invalid form | Re-render the form with the errors list and the submitted values |
| Missing privilege | `require_privilege()` → flash error, back |
| State change by GET | Refused by `require_post()` |
| Upload problem | `ImageUpload::save()` returns `['error' => message]`, shown like a validation error |
| Unknown address | 404 override (`Pages::not_found`) |

## 10. Checklist for a new module

- [ ] Module generated (`make:module`) or copied and registered (`make:module-route`).
- [ ] `/teams` returns 200 and has the right `<title>`.
- [ ] Migration created and run; upload folder added to `app/Config/Uploads.php` if needed.
- [ ] Model methods return `false` / `null` / data; no SQL outside the model.
- [ ] Views escape output and use the site layout.
- [ ] Sitepanel: controller, route entry, `tbl_admin_sections` row (seeder), views.
- [ ] Sub-admin without the privilege cannot add, edit or delete.
- [ ] Existing pages still load (`/`, `/aboutus`, `/blogs`, `/testimonials`, `/contactus`).
