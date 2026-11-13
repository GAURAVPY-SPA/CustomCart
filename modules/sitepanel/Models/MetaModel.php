<?php

namespace Modules\Sitepanel\Models;

use App\Models\BaseModel;

/**
 * Meta tags (wl_meta_tags): the SEO data of the website's pages, and the
 * website's URL registry, as in the reference (its router resolved every
 * request through page_url): every page and module address of the website
 * is a row, routed by its entity_type (route_for(), used by
 * app/Config/ModuleRoutes.php) with one rule for all of them:
 * '<module>/<method>' + entity_id (0 = the module's page, else one record's
 * page). Module pages are registered by php spark make:module /
 * make:module-route, record pages by save_entity_page(). Rows of other
 * types only hold SEO data.
 */
class MetaModel extends BaseModel
{
    /**
     * entity_type of a static page (Sitepanel > Manage Static Pages), as in
     * the reference: 'pages/index/<friendly_url>', entity_id = page_id. Routed
     * like every row (Pages::index/<page_id>); the prefix is how the static
     * pages' rows are found (PagesModel, seeder). The layout the page is shown
     * in is its own (wl_cms_pages.layout).
     */
    public const PAGE_TYPE_PREFIX = 'pages/index/';

    /**
     * page_url of the home page (the site root resolves through it).
     */
    public const HOME_URL = 'home';

    /**
     * First address segments the registry never routes (their own routes:
     * modules/sitepanel/Config/Routes.php, app/Config/Routes.php), so no
     * page, category or product may use them as its address.
     */
    public const RESERVED_SEGMENTS = ['sitepanel', 'uploads', 'uploaded_files'];

    /**
     * Whether an address can't be a page's: it starts with a reserved
     * segment, or a route defined in code already answers it.
     */
    public static function reserved(string $page_url): bool
    {
        $page_url = trim($page_url, '/');
        if (in_array(explode('/', $page_url)[0], self::RESERVED_SEGMENTS, true)) {
            return true;
        }

        foreach (service('routes')->getRoutes('GET') as $route => $handler) {
            if ($route === $page_url || (str_contains($route, '(') && preg_match('#^' . $route . '$#', $page_url) === 1)) {
                return true;
            }
        }

        return false;
    }

    /**
     * HTTP methods of a routed page (POST: its forms, e.g. a blog comment,
     * the contact form; pages without a form ignore it, CSRF checks it).
     */
    public const ROUTE_METHODS = ['GET', 'POST'];

    /**
     * entity_type of a routed row, as the reference's router read it
     * (MY_Router::_db_routes(): $route[page_url] = entity_type . '/' . entity_id):
     * '<module>/<method>', optionally followed by '/<anything>' that only
     * describes the row (static pages: 'pages/index/<address>').
     */
    public const ENTITY_TYPE = '/^([a-z][a-z0-9_]*)\/([a-z][a-z0-9_]*)(?:\/[^\s]*)?$/';

    /**
     * entity_type of a static page's row.
     */
    public static function page_type(string $friendly_url): string
    {
        return self::PAGE_TYPE_PREFIX . $friendly_url;
    }

    /**
     * entity_type of a module's page: '<module>/<method>', e.g.
     * entity_type('teams') = 'teams/index' (landing page, entity_id 0),
     * entity_type('blog', 'detail') = 'blog/detail' (one record, entity_id = its id).
     */
    public static function entity_type(string $module, string $method = 'index'): string
    {
        return $module . '/' . $method;
    }

    /**
     * The route of a row: [controller::method[/entity_id], HTTP methods], or
     * NULL when the row only holds SEO data. One rule for every row, as in
     * the reference:
     *
     *   entity_type '<module>/<method>[/...]', entity_id 0  => Modules\<Module>\Controllers\<Module>::<method>
     *   entity_type '<module>/<method>[/...]', entity_id N  => Modules\<Module>\Controllers\<Module>::<method>/N
     *
     * e.g. home/index => Home::index, blog/detail #19 => Blog::detail/19,
     * pages/index/aboutus #2 => Pages::index/2, teams/detail #7 => Teams::detail/7.
     *
     * @param array $row page_url, entity_type, entity_id
     */
    public static function route_for(array $row): ?array
    {
        $handler = self::module_route((string) $row['entity_type']);
        if ($handler === null) {
            return null;
        }
        $id = (int) $row['entity_id'];

        return [$id > 0 ? $handler . '/' . $id : $handler, self::ROUTE_METHODS];
    }

    /**
     * Whether a row is the route of a page (its address can't simply be deleted).
     */
    public static function is_route(array $row): bool
    {
        return self::module_route((string) $row['entity_type']) !== null;
    }

    /**
     * Module folder (snake_case) => namespace / controller name (StudlyCase):
     * contact_form => ContactForm, as php spark make:module names them.
     */
    public static function studly(string $snake): string
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $snake)));
    }

    /**
     * The handler an entity_type names, by convention: 'teams/index' =>
     * \Modules\Teams\Controllers\Teams::index. NULL unless that controller
     * exists (its namespace registered in app/Config/Autoload.php) and the
     * method is one of its public actions, so a row can never reach
     * anything else (and an unknown or removed module gets the 404 page).
     */
    public static function module_route(string $type): ?string
    {
        // The sitepanel has its own routes (modules/sitepanel/Config/Routes.php).
        if (! preg_match(self::ENTITY_TYPE, $type, $m) || $m[1] === 'sitepanel') {
            return null;
        }

        $class = '\Modules\\' . self::studly($m[1]) . '\Controllers\\' . self::studly($m[1]);
        try {
            if (! class_exists($class) || ! is_subclass_of($class, \CodeIgniter\Controller::class) || ! method_exists($class, $m[2])) {
                return null;
            }
            $method = new \ReflectionMethod($class, $m[2]);
        } catch (\Throwable $e) {
            log_message('error', 'MetaModel::module_route(' . $type . '): ' . $e->getMessage());

            return null;
        }

        // Only the module's own public actions (not initController() and the like).
        if (! $method->isPublic() || $method->isStatic() || $method->isConstructor() || str_starts_with($method->getName(), '_')
            || ! str_starts_with($method->getDeclaringClass()->getName(), 'Modules\\')) {
            return null;
        }

        return $class . '::' . $method->getName();
    }

    /**
     * Add or update the row of one record's page (a blog post, a team
     * member...): entity_type '<module>/<method>', entity_id = the record's
     * id, page_url = its address; empty SEO fields are filled from $defaults.
     * In a transaction; refused (nothing changed) when another row holds the
     * address.
     *
     * @param array $meta     meta_title, meta_description, meta_keyword ('' = default)
     * @param array $defaults the same keys, used for the fields left empty
     *
     * @return string|null NULL when saved, else the reason it was not
     */
    public function save_entity_page(string $type, int $id, string $page_url, array $meta, array $defaults = []): ?string
    {
        if ($id < 1 || self::module_route($type) === null) {
            return 'The page type "' . $type . '" is not a public method of a module controller.';
        }
        if (! $this->db_ready()) {
            return 'The database is not available.';
        }

        $values = [];
        foreach (['meta_title', 'meta_description', 'meta_keyword'] as $key) {
            $value        = trim((string) ($meta[$key] ?? ''));
            $values[$key] = $value !== '' ? $value : trim((string) ($defaults[$key] ?? ''));
        }

        try {
            $this->db->transException(true)->transStart();

            $own = $this->table('wl_meta_tags')->where(['entity_type' => $type, 'entity_id' => $id])->get()->getRowArray();
            $at  = $this->table('wl_meta_tags')->where('page_url', $page_url)->get()->getRowArray();
            if ($at && (! $own || (int) $at['meta_id'] !== (int) $own['meta_id'])) {
                $this->db->transRollback();

                return 'The address "' . $page_url . '" is already used by another page (meta_id ' . $at['meta_id'] . ').';
            }

            if ($own) {
                $this->table('wl_meta_tags')->where('meta_id', (int) $own['meta_id'])->update($values + ['page_url' => $page_url]);
            } else {
                $this->table('wl_meta_tags')->insert($values + [
                    'is_fixed' => 'N', 'entity_type' => $type, 'entity_id' => $id, 'page_url' => $page_url, 'status' => '1',
                ]);
            }
            $this->db->transComplete();

            return null;
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'MetaModel::save_entity_page(' . $type . ', ' . $id . '): ' . $e->getMessage());

            return 'The page could not be saved: ' . $e->getMessage();
        } finally {
            $this->db->transException(false);
        }
    }

    /**
     * Register a module page: its wl_meta_tags row (page_url => entity_type)
     * with its first SEO data, in a transaction. Nothing is changed when the
     * address or the entity_type is already registered.
     *
     * @param array $meta meta_title, meta_description, meta_keyword
     *
     * @return string|null NULL when registered, else the reason it was not
     */
    public function register_module_page(string $type, string $page_url, array $meta): ?string
    {
        if (! $this->db_ready()) {
            return 'The database is not available.';
        }

        try {
            $this->db->transException(true)->transStart();

            $at = $this->table('wl_meta_tags')->where('page_url', $page_url)->get()->getRowArray();
            if ($at) {
                $this->db->transRollback();

                return 'The address "' . $page_url . '" is already registered in wl_meta_tags (meta_id ' . $at['meta_id'] . ', entity_type "' . $at['entity_type'] . '").';
            }
            $own = $this->table('wl_meta_tags')->where(['entity_type' => $type, 'entity_id' => 0])->get()->getRowArray();
            if ($own) {
                $this->db->transRollback();

                return 'The module page "' . $type . '" is already registered in wl_meta_tags at the address "' . $own['page_url'] . '" (meta_id ' . $own['meta_id'] . ').';
            }

            $this->table('wl_meta_tags')->insert([
                'is_fixed'         => 'Y',
                'entity_type'      => $type,
                'entity_id'        => 0,
                'page_url'         => $page_url,
                'meta_title'       => (string) ($meta['meta_title'] ?? ''),
                'meta_description' => (string) ($meta['meta_description'] ?? ''),
                'meta_keyword'     => (string) ($meta['meta_keyword'] ?? ''),
                'status'           => '1',
            ]);
            $this->db->transComplete();

            return null;
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'MetaModel::register_module_page(' . $type . '): ' . $e->getMessage());

            return 'The page could not be registered: ' . $e->getMessage();
        } finally {
            $this->db->transException(false);
        }
    }

    /**
     * The row of a page address (any status: a deactivated row only loses its
     * SEO data, as in the reference).
     *
     * @return array|false|null page_url, entity_type, entity_id
     */
    public function get_route(string $page_url)
    {
        if (! $this->db_ready()) {
            return false;
        }

        return $this->single_row($this->table('wl_meta_tags')
            ->select('page_url, entity_type, entity_id')
            ->where('page_url', $page_url)
            ->limit(1)
            ->get());
    }

    /**
     * The row of a module page (entity_id 0) by its entity_type, e.g.
     * 'products/index' => its page_url ('online-store').
     *
     * @return array|false|null page_url, entity_type, entity_id
     */
    public function get_route_of(string $entity_type)
    {
        if (! $this->db_ready()) {
            return false;
        }

        return $this->single_row($this->table('wl_meta_tags')
            ->select('page_url, entity_type, entity_id')
            ->where(['entity_type' => $entity_type, 'entity_id' => 0])
            ->orderBy('meta_id', 'ASC')
            ->limit(1)
            ->get());
    }

    /**
     * Addresses of the website's pages (rows with a route; static pages
     * only while published), read once per request.
     *
     * @return list<string>
     */
    public function routed_urls(): array
    {
        static $urls = null;

        if ($urls === null) {
            $urls = [];
            if ($this->db_ready()) {
                $rows = $this->all_rows($this->table('wl_meta_tags AS m')
                    ->select('m.page_url, m.entity_type, m.entity_id, p.status AS page_status')
                    ->join('wl_cms_pages AS p', "m.entity_type LIKE 'pages/%' AND p.page_id = m.entity_id", 'left')
                    ->get());

                foreach (is_array($rows) ? $rows : [] as $row) {
                    $is_page = str_starts_with((string) $row['entity_type'], 'pages/');
                    if (self::route_for($row) !== null && (! $is_page || $row['page_status'] === '1')) {
                        $urls[] = $row['page_url'];
                    }
                }
            }
        }

        return $urls;
    }

    /**
     * The row holding a page address, whatever it is used for.
     *
     * @return array|false|null
     */
    public function get_by_page_url(string $page_url)
    {
        return $this->find('wl_meta_tags', ['page_url' => $page_url]);
    }
    /**
     * @param string $keyword Search page slugs
     * @param string $status  '1', '0' or '' for all
     *
     * @return array|false
     */
    public function get_meta(int $offset = 0, int $limit = 20, string $keyword = '', string $status = '')
    {
        return $this->paged_rows('wl_meta_tags', 'meta_id DESC', $offset, $limit, static function ($db) use ($keyword, $status) {
            if ($keyword !== '') {
                $db->like('page_url', $keyword);
            }
            if ($status !== '') {
                $db->where('status', $status);
            }
        });
    }

    /**
     * @param int|string $id
     *
     * @return array|false|null
     */
    public function get_record_by_id($id)
    {
        return $this->find('wl_meta_tags', ['meta_id' => (int) $id]);
    }

    /**
     * @param string $page_url Normalized public slug
     *
     * @return string 'available', 'exists', 'invalid', 'unavailable' or 'error'
     */
    public function check_page_url($page_url, int $except_id = 0): string
    {
        if (! $this->db_ready()) {
            return 'error';
        }
        if (! $this->db->tableExists('wl_meta_tags')) {
            log_message('error', 'Meta_model: wl_meta_tags is missing; apply apps/sql/007_meta_tags.sql.');

            return 'unavailable';
        }

        $page_url = strtolower(trim((string) $page_url, " \t\n\r\0\x0B/"));
        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $page_url) !== 1) {
            return 'invalid';
        }

        foreach ([$page_url, 'pages/' . $page_url] as $path) {
            $where = ['page_url' => $path];
            if ($except_id > 0) {
                $where['meta_id <>'] = $except_id;
            }

            $count = $this->findCount('wl_meta_tags', $where);
            if ($count === false) {
                return 'error';
            }
            if ($count > 0) {
                return 'exists';
            }
        }

        return 'available';
    }

    /**
     * Meta tag row owned by a record of another module (entity_type +
     * entity_id, as in the reference project), e.g. ('blog', 4).
     *
     * @return array|false|null
     */
    public function get_entity_meta(string $type, $id, bool $active_only = false)
    {
        if (! $this->db_ready()) {
            return false;
        }
        if (! $this->db->tableExists('wl_meta_tags')) {
            return null;
        }

        $where = ['entity_type' => $type, 'entity_id' => (int) $id];
        $active_only && $where['status'] = '1';

        return $this->single_row($this->table('wl_meta_tags')->where($where)->orderBy('meta_id', 'DESC')->limit(1)->get());
    }

    /**
     * Create, update or (when every value is empty) delete the meta tag row
     * of a record; its page_url always follows the record's public URL.
     *
     * @param string $page_url Public URI, e.g. 'blogs/<slug>'
     * @param array  $values   meta_title, meta_keyword, meta_description
     */
    public function save_entity_meta(string $type, $id, string $page_url, array $values): bool
    {
        $row = $this->get_entity_meta($type, $id);
        if ($row === false) {
            return false;
        }

        $values = [
            'meta_title'       => trim((string) $values['meta_title']),
            'meta_keyword'     => trim((string) $values['meta_keyword']),
            'meta_description' => trim((string) $values['meta_description']),
        ];

        if (implode('', $values) === '') {
            return $row ? $this->delete_entity_meta($type, $id) : true;
        }

        $values['page_url'] = $page_url;
        if ($row) {
            return $this->safe_update('wl_meta_tags', $values, ['meta_id' => (int) $row['meta_id']]);
        }

        $values += ['is_fixed' => 'N', 'entity_type' => $type, 'entity_id' => (int) $id, 'status' => '1'];

        return $this->safe_insert('wl_meta_tags', $values) !== false;
    }

    public function delete_entity_meta(string $type, $id): bool
    {
        return $this->safe_delete('wl_meta_tags', ['entity_type' => $type, 'entity_id' => (int) $id]);
    }

    /**
     * @param string $page_url Friendly slug (legacy pages/<slug> rows are also read)
     *
     * @return array|false|null
     */
    public function get_active_for_url($page_url)
    {
        if (! $this->db_ready()) {
            return false;
        }
        if (! $this->db->tableExists('wl_meta_tags')) {
            return null;
        }

        $page_url = strtolower(trim((string) $page_url, " \t\n\r\0\x0B/"));
        if (str_starts_with($page_url, 'pages/')) {
            $page_url = substr($page_url, 6);
        }
        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $page_url) !== 1) {
            return null;
        }

        foreach ([$page_url, 'pages/' . $page_url] as $path) {
            $row = $this->single_row($this->table('wl_meta_tags')->where(['page_url' => $path, 'status' => '1'])
                ->orderBy('meta_id', 'DESC')
                ->limit(1)
                ->get());
            if ($row !== null) {
                return $row;
            }
        }

        return null;
    }
}
