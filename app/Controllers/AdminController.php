<?php

namespace App\Controllers;

use App\Exceptions\HaltException;
use App\Models\UtilsModel;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Session\Session;
use Modules\Sitepanel\Libraries\AdminAccess;
use Modules\Sitepanel\Libraries\AdminAuth;
use App\Libraries\ImageUpload;

/**
 * Base controller for every protected sitepanel page.
 *
 * boot() enforces authentication and section permissions before a module
 * controller's init() or action runs, so a protected action never runs for
 * a visitor who isn't signed in as an active administrator allowed to open
 * this controller. (The sitepanelauth route filter checks the sign-in too.)
 *
 * As in the reference project, controllers render their page views directly
 * ($this->render('module/view_name', $data)) and each page view includes
 * includes/header and includes/footer itself. The data those includes need
 * (admin, menu, flash messages, site name...) is shared with every view of
 * the request; the active sidebar item is $menu_highlight, set by each
 * controller.
 *
 * Redirects end the request immediately (redirect_now(), as CodeIgniter 3's
 * redirect() did), so the helpers below can be called from any depth.
 */
abstract class AdminController extends BaseController
{
    /**
     * Rows per list page.
     */
    public const PAGESIZE = 20;

    /**
     * Helpers for every sitepanel controller and view
     * (CodeIgniter 3: modules/sitepanel/config/autoload.php); form and text
     * are autoloaded for the whole application (Config\Autoload::$helpers).
     *
     * @var list<string>
     */
    protected $helpers = ['sitepanel', 'admin'];

    /**
     * Signed-in administrator (admin_id, admin_type, admin_name, admin_username, admin_email).
     */
    protected array $admin = [];

    /**
     * Privilege codes held on this controller (AdminAccess::PRIV_*).
     *
     * @var list<int>
     */
    protected array $permissions = [];

    /**
     * Privileges on this controller, named as in the reference project.
     * Shared with the views as $addPrvg, $editPrvg, ...
     */
    public bool $addPrvg        = false;
    public bool $editPrvg       = false;
    public bool $activatePrvg   = false;
    public bool $deactivatePrvg = false;
    public bool $deletePrvg     = false;

    /**
     * List page URI of the module, e.g. 'sitepanel/banners'.
     */
    protected string $base = 'sitepanel/dashboard';

    /**
     * Singular record name for messages, e.g. 'Banner'.
     */
    protected string $item = 'Record';

    /**
     * Words for status messages: [on, off].
     */
    protected array $status_words = ['activated', 'deactivated'];

    /**
     * Active sidebar item (tbl_admin_sections.section_controller).
     */
    protected string $menu_highlight = '';

    protected Session $session;
    protected AdminAuth $admin_auth;
    protected AdminAccess $admin_access;

    protected function boot(): void
    {
        $this->session      = session();
        $this->admin_auth   = service('adminAuth');
        $this->admin_access = service('adminAccess');

        $this->admin = $this->admin_auth->require_login();

        // Authenticated pages must never be served from a browser or proxy cache.
        $this->response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $this->response->setHeader('Pragma', 'no-cache');

        $permissions = $this->admin_access->permissions_for($this->admin, $this->section());

        if ($permissions === false) {
            throw HaltException::error('The sitepanel is temporarily unavailable. Please try again shortly.', 503, 'Service unavailable');
        }

        if (empty($permissions)) {
            $this->session->setFlashdata('sitepanel_error', "You don't have permission to open that section.");
            redirect_now('sitepanel/dashboard');
        }
        $this->permissions = $permissions;

        $this->addPrvg        = $this->can(AdminAccess::PRIV_ADD);
        $this->editPrvg       = $this->can(AdminAccess::PRIV_EDIT);
        $this->activatePrvg   = $this->can(AdminAccess::PRIV_ACTIVATE);
        $this->deactivatePrvg = $this->can(AdminAccess::PRIV_DEACTIVATE);
        $this->deletePrvg     = $this->can(AdminAccess::PRIV_DELETE);

        // Data for includes/header, the sidebar (dashboard/admin_left_view) and includes/footer.
        $this->view_data += [
            'heading_title'  => 'Sitepanel',
            'breadcrumbs'    => [], // label => url (NULL for the current page)
            'admin'          => $this->admin,
            'previous_login' => $this->admin_auth->previous_login(), // header "Last logged in"
            'menu'           => $this->admin_access->menu($this->admin),
            'flash_error'    => $this->session->getFlashdata('sitepanel_error'),
            'flash_success'  => $this->session->getFlashdata('sitepanel_success'),
        ];
    }

    /**
     * Section name of this controller as stored in
     * tbl_admin_sections.section_controller: the CodeIgniter 3 controller
     * name, e.g. BlogCategories -> 'blog_categories'.
     */
    protected function section(): string
    {
        $class = substr(strrchr('\\' . static::class, '\\'), 1);

        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $class));
    }

    /**
     * Whether the admin holds a privilege on this controller, e.g.
     * $this->can(AdminAccess::PRIV_EDIT) before showing an edit action.
     */
    protected function can(int $privilege): bool
    {
        return in_array($privilege, $this->permissions, true);
    }

    /**
     * Render a sitepanel page view (module-relative name, e.g.
     * 'banner/view_banner_list') with the shared sitepanel data.
     */
    protected function render(string $view, array $data = []): string
    {
        return sp_view($view, $data + $this->view_data + [
            'menu_highlight' => $this->menu_highlight,
            'addPrvg'        => $this->addPrvg,
            'editPrvg'       => $this->editPrvg,
            'activatePrvg'   => $this->activatePrvg,
            'deactivatePrvg' => $this->deactivatePrvg,
            'deletePrvg'     => $this->deletePrvg,
            'image_upload'   => new ImageUpload(), // list thumbnails: $image_upload->path(folder, file)
        ]);
    }

    /* -----------------------------------------------------------------
     * Shared list actions (the reference project's Admin_Controller::update_status).
     * Module controllers expose them as POST-only routes:
     *   public function status($id) { $this->update_status('wl_x', 'id', $id); }
     * ----------------------------------------------------------------- */

    /**
     * POST {status: 1|0, page}: activate or deactivate one row of $table.
     *
     * @param string     $auto_field Primary key column
     * @param int|string $id
     */
    protected function update_status(string $table, string $auto_field, $id): never
    {
        $this->require_post();
        $active = ($this->request->getPost('status') === '1');
        $this->require_privilege($active ? AdminAccess::PRIV_ACTIVATE : AdminAccess::PRIV_DEACTIVATE);

        $utils = new UtilsModel();
        $where = [$auto_field => (int) $id];
        $this->record_or_back($utils->find($table, $where));

        if ($utils->safe_update($table, ['status' => $active ? '1' : '0'], $where)) {
            $this->session->setFlashdata('sitepanel_success', $this->item . ' ' . $this->status_words[$active ? 0 : 1] . '.');
        } else {
            $this->session->setFlashdata('sitepanel_error', $this->item . ' could not be updated. Please try again.');
        }

        $this->back();
    }

    /**
     * POST {page}: delete one row of $table (and its uploaded image).
     *
     * @param string      $auto_field   Primary key column
     * @param int|string  $id
     * @param string|null $image_column Column holding an uploaded image file name
     * @param string|null $image_folder ImageUpload folder of that image
     */
    protected function delete_record(string $table, string $auto_field, $id, ?string $image_column = null, ?string $image_folder = null): never
    {
        $this->require_post();
        $this->require_privilege(AdminAccess::PRIV_DELETE);

        $utils = new UtilsModel();
        $where = [$auto_field => (int) $id];
        $row   = $this->record_or_back($utils->find($table, $where));

        if ($utils->safe_delete($table, $where)) {
            if ($image_column) {
                (new ImageUpload())->delete($image_folder, $row[$image_column]);
            }
            $this->session->setFlashdata('sitepanel_success', $this->item . ' deleted.');
        } else {
            $this->session->setFlashdata('sitepanel_error', $this->item . ' could not be deleted. Please try again.');
        }

        $this->back();
    }

    /* -----------------------------------------------------------------
     * Helpers for module controllers
     * ----------------------------------------------------------------- */

    /**
     * 1-based list page from ?page=.
     */
    protected function current_page(): int
    {
        $page = (int) $this->request->getGet('page');

        return $page > 1 ? $page : 1;
    }

    /**
     * A row loaded by a model (get_x_by_id); otherwise back to the list with a message.
     *
     * @param array|false|null $row
     */
    protected function record_or_back($row): array
    {
        if (! is_array($row)) {
            $this->session->setFlashdata('sitepanel_error', $row === false
                ? $this->item . ' could not be loaded. Please try again.'
                : $this->item . ' not found. It may have been deleted already.');
            $this->back();
        }

        return $row;
    }

    /**
     * Back to the list page the action was started from.
     */
    protected function back(array $query = []): never
    {
        $page  = (int) $this->request->getPost('page');
        $query = $page > 1 ? ['page' => $page] + $query : $query;
        redirect_now($this->base . ($query ? '?' . http_build_query($query) : ''));
    }

    /**
     * Validate a bulk selection before any model operation.
     *
     * @return false|list<int>
     */
    protected function selected_ids()
    {
        $values = $this->request->getPost('ids');
        if (! is_array($values) || ! $values || count($values) > self::PAGESIZE) {
            return false;
        }

        $ids = [];

        foreach ($values as $value) {
            if (! is_string($value) || ! preg_match('/^[1-9][0-9]*$/D', $value) || (string) (int) $value !== $value) {
                return false;
            }
            $ids[] = (int) $value;
        }

        return count(array_unique($ids)) === count($ids) ? $ids : false;
    }

    /**
     * Check that every selected ID still belongs to this module.
     *
     * @param array|false $rows
     */
    protected function selected_rows_match($rows, array $ids): bool
    {
        if (! is_array($rows) || count($rows) !== count($ids)) {
            $this->session->setFlashdata('sitepanel_error', 'The selected records could not be verified. Please refresh the list and try again.');

            return false;
        }

        return true;
    }

    /**
     * Bulk delete for controllers whose single delete does extra checks first
     * (SEO metadata, categories still in use). Validates the request (POST,
     * Delete privilege, selected ids on this page), runs $before_delete($rows),
     * which returns an error message to stop or NULL to continue, then deletes
     * through process_bulk_action(). Only the "delete" action is accepted.
     *
     * @param object   $model         Needs $get_method(ids) and bulk_delete(ids)
     * @param array    $query         List filters to return to
     * @param callable $before_delete function (array $rows): string|null
     */
    protected function bulk_delete_checked(object $model, string $get_method, string $item, array $query, callable $before_delete, ?string $image_column = null, ?string $image_folder = null): never
    {
        $this->require_post();
        if ($this->request->getPost('bulk_action') !== 'delete') {
            $this->session->setFlashdata('sitepanel_error', 'Choose a valid bulk action.');
            $this->back($query);
        }
        $this->require_privilege(AdminAccess::PRIV_DELETE);

        $ids = $this->selected_ids();
        if ($ids === false) {
            $this->session->setFlashdata('sitepanel_error', 'Select one or more valid records on this page.');
            $this->back($query);
        }
        $rows = $model->{$get_method}($ids);
        if (! $this->selected_rows_match($rows, $ids)) {
            $this->back($query);
        }

        $error = $before_delete($rows);
        if ($error !== null) {
            $this->session->setFlashdata('sitepanel_error', $error);
            $this->back($query);
        }

        $this->process_bulk_action($model, $get_method, $item, $query, $image_column, $image_folder);
    }

    /**
     * Process a module-specific bulk action after validating permission and IDs.
     */
    protected function process_bulk_action(object $model, string $get_method, string $item, array $query = [], ?string $image_column = null, ?string $image_folder = null): never
    {
        $this->require_post();
        $action = (string) $this->request->getPost('bulk_action');
        if (! in_array($action, ['activate', 'deactivate', 'delete'], true)) {
            $this->session->setFlashdata('sitepanel_error', 'Choose a valid bulk action.');
            $this->back($query);
        }

        if ($action === 'delete') {
            $this->require_privilege(AdminAccess::PRIV_DELETE);
        } else {
            $this->require_privilege($action === 'activate' ? AdminAccess::PRIV_ACTIVATE : AdminAccess::PRIV_DEACTIVATE);
        }

        $ids = $this->selected_ids();
        if ($ids === false) {
            $this->session->setFlashdata('sitepanel_error', 'Select one or more valid records on this page.');
            $this->back($query);
        }

        $rows = $model->{$get_method}($ids);
        if (! $this->selected_rows_match($rows, $ids)) {
            $this->back($query);
        }

        if ($action === 'delete') {
            $success = $model->bulk_delete($ids);
            if ($success && $image_column !== null) {
                $images = new ImageUpload();

                foreach ($rows as $row) {
                    $images->delete($image_folder, $row[$image_column]);
                }
            }
            $verb = 'deleted';
        } else {
            $active  = $action === 'activate';
            $success = $model->bulk_update_status($ids, $active ? '1' : '0');
            $verb    = $this->status_words[$active ? 0 : 1];
        }

        if ($success) {
            $count = count($ids);
            $this->session->setFlashdata('sitepanel_success', $count . ' ' . ($count === 1 ? $item : 'records') . ' ' . $verb . '.');
        } else {
            $this->session->setFlashdata('sitepanel_error', $item . ' records could not be changed. Please try again.');
        }

        $this->back($query);
    }

    /**
     * State-changing actions only run for POST (CSRF-checked); anything
     * else goes back to the list without changing data.
     */
    protected function require_post(): void
    {
        if ($this->request->getMethod() !== 'POST') {
            redirect_now($this->base);
        }
    }

    /**
     * @param int $privilege AdminAccess::PRIV_*
     */
    protected function require_privilege(int $privilege): void
    {
        if (! $this->can($privilege)) {
            $this->session->setFlashdata('sitepanel_error', "You don't have permission to do that.");
            $this->back();
        }
    }

    /**
     * Trimmed POST value, NULL when empty.
     */
    protected function nullable(string $field): ?string
    {
        $value = trim((string) $this->request->getPost($field));

        return $value === '' ? null : $value;
    }

    /**
     * POST (multipart, field "upload"): an image chosen in the CKEditor image
     * dialog's Upload tab (filebrowserImageUploadUrl, set from the textarea's
     * data-sp-upload-url by assets/sitepanel/js/editor.js), as the
     * reference's CKFinder QuickUpload did. Shared by every section with an
     * editor through its own <section>/upload_image route, so it answers
     * with that section's permissions (add or edit). Saved by ImageUpload in
     * uploads/editor/. The reply, as CKFinder's: JSON {uploaded, url} or
     * {uploaded: 0, error: {message}} for CKEditor's XHR upload, or for the
     * form method the callback page that puts the URL (or error) into the dialog.
     *
     * The dialog posts from its own frame without the csrf_customcart token, so
     * the route is left out of the csrf filter (Config\Filters) and checked
     * here with CKEditor's double-submit token instead, as CKFinder does: the
     * ckCsrfToken field must match the ckCsrfToken cookie.
     */
    public function upload_image(): ResponseInterface
    {
        $token  = (string) $this->request->getPost('ckCsrfToken');
        $cookie = (string) $this->request->getCookie('ckCsrfToken');

        if ($this->request->getMethod() !== 'POST' || strlen($token) !== 40 || ! hash_equals($cookie, $token)) {
            $result = ['error' => 'The upload could not be verified. Please reload the page and try again.'];
        } elseif (! $this->can(AdminAccess::PRIV_ADD) && ! $this->can(AdminAccess::PRIV_EDIT)) {
            $result = ['error' => "You don't have permission to do that."];
        } else {
            $result = (new ImageUpload())->save('upload', 'editor', 'Image');
        }

        $url      = isset($result['file']) ? base_url('uploads/editor/' . $result['file']) : '';
        $message  = $result['error'] ?? '';
        $func_num = (string) $this->request->getGet('CKEditorFuncNum');

        // CKEditor 4.9+ uploads with XHR by default (filebrowserUploadMethod 'xhr', ?responseType=json)
        // and reads JSON; the older form method posts in a frame and needs the callback page.
        if ($this->request->getGet('responseType') === 'json' || ! ctype_digit($func_num)) {
            return $this->response->setJSON($url !== ''
                ? ['uploaded' => 1, 'fileName' => $result['file'], 'url' => $url]
                : ['uploaded' => 0, 'error' => ['message' => $message]]);
        }

        $js   = static fn (string $value): string => json_encode($value, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES);
        $body = '<script>window.parent.CKEDITOR.tools.callFunction(' . $func_num . ', ' . $js($url) . ', ' . $js($message) . ');</script>';

        return $this->response->setContentType('text/html')->setBody($body);
    }
}
