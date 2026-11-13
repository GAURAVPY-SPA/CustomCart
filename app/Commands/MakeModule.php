<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Autoload;
use Modules\Pages\Models\PagesModel;
use Modules\Sitepanel\Controllers\Staticpages;
use Modules\Sitepanel\Models\MetaModel;
use ParseError;
use Throwable;

/**
 * Generates a new website module in modules/, following the layout of the
 * existing ones (testimonials, blog...):
 *
 *   php spark make:module teams [--url our-teams]
 *
 *   modules/teams/Controllers/Teams.php     extends App\Controllers\PublicController, index()
 *   modules/teams/Models/TeamsModel.php     extends App\Models\BaseModel
 *   modules/teams/Views/teams.php           index page in the site layout (top_application / bottom_application)
 *
 * The folder is the snake_case name and the namespace the StudlyCase name
 * (contact_form => Modules\ContactForm), registered in
 * app/Config/Autoload.php like every other module.
 *
 * The module's page is not added to a route file: it is registered in
 * wl_meta_tags, the website's URL registry (as in the reference,
 * MY_Router::_db_routes), with its first SEO data:
 *
 *   entity_type teams/index   page_url teams   (contact_form => contact-form)
 *
 * app/Config/ModuleRoutes.php routes the address through that row
 * (MetaModel::route_for(): '<module>/<method>' => Modules\<Module>\Controllers\<Module>::<method>).
 *
 * Nothing is ever overwritten: the command refuses a module whose folder,
 * namespace, address or wl_meta_tags row already exists, and if any step
 * fails it removes what it created and restores the config file it changed.
 *
 * A module made by copying another one is registered with
 * php spark make:module-route (MakeModuleRoute).
 */
class MakeModule extends BaseCommand
{
    /**
     * Longest accepted module name (snake_case).
     */
    public const MAX_NAME_LENGTH = 40;

    /**
     * Page address: small letters, digits and single hyphens (as Manage Static Pages).
     */
    public const URL_PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    protected $group       = 'Generators';
    protected $name        = 'make:module';
    protected $description = 'Generates a new website module in modules/ (controller, model, view) and registers its page in wl_meta_tags.';
    protected $usage       = 'make:module <name> [--url <address>] [--no-status]';
    protected $arguments   = [
        'name' => 'Module name in snake_case or StudlyCase, e.g. teams or ContactForm.',
    ];
    protected $options = [
        '--url' => 'Page address (default: the name with hyphens, e.g. contact_form => contact-form).',
        '--no-status' => 'The table has no status column: the model gets no status filter, get_active_*() or bulk_update_status().',
    ];

    /**
     * Words PHP doesn't allow as a class name (case-insensitive).
     */
    protected const RESERVED_WORDS = [
        'abstract', 'and', 'array', 'as', 'bool', 'break', 'callable', 'case', 'catch', 'class', 'clone',
        'const', 'continue', 'declare', 'default', 'do', 'echo', 'else', 'elseif', 'empty', 'enddeclare',
        'endfor', 'endforeach', 'endif', 'endswitch', 'endwhile', 'enum', 'eval', 'exit', 'extends', 'false',
        'final', 'finally', 'float', 'fn', 'for', 'foreach', 'function', 'global', 'goto', 'if', 'implements',
        'include', 'instanceof', 'insteadof', 'int', 'interface', 'isset', 'iterable', 'list', 'match', 'mixed',
        'namespace', 'never', 'new', 'null', 'object', 'or', 'parent', 'print', 'private', 'protected',
        'public', 'readonly', 'require', 'return', 'self', 'static', 'string', 'switch', 'throw', 'trait',
        'true', 'try', 'unset', 'use', 'var', 'void', 'while', 'xor', 'yield',
    ];

    /**
     * Names Windows doesn't allow as a folder name.
     */
    protected const DEVICE_NAMES = '/^(con|prn|aux|nul|com\d|lpt\d)$/';

    /**
     * Files and folders created by this run, removed again on failure.
     *
     * @var list<string>
     */
    protected array $created_files = [];

    /**
     * @var list<string>
     */
    protected array $created_dirs = [];

    /**
     * Original contents of the config files this run changed, path => contents.
     *
     * @var array<string, string>
     */
    protected array $changed_files = [];

    public function run(array $params)
    {
        if (CLI::getOption('force') !== null || CLI::getOption('f') !== null) {
            return $this->fail('--force is not supported: this command never overwrites a module. '
                . 'To recreate one, delete its folder in modules/, its line in app/Config/Autoload.php and its wl_meta_tags row first.');
        }

        $module = $this->module($params);
        if (is_string($module)) {
            return $this->fail($module);
        }
        [$snake, $studly, $title, $url] = $module;

        $modules_dir = ROOTPATH . 'modules';
        $conflict    = $this->conflict($modules_dir, $snake, $studly) ?? $this->url_conflict($url, $snake);
        if ($conflict !== null) {
            return $this->fail($conflict);
        }

        $module_dir = $modules_dir . DIRECTORY_SEPARATOR . $snake;
        $files      = $this->files($snake, $studly, $title, $url);

        try {
            foreach ($files as $path => $contents) {
                $this->write_new($module_dir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path), $contents);
            }
            $this->register_namespace($snake, $studly);

            // Last: the database row is the only step that can't be undone by rollback().
            $error = $this->register_page($snake, $title, $url);
            if ($error !== null) {
                throw new \RuntimeException($error);
            }
        } catch (Throwable $e) {
            $this->rollback();

            return $this->fail(rtrim($e->getMessage(), '.') . '. Nothing was changed.');
        }

        CLI::write(CLI::color('Module ' . $snake . ' created successfully.', 'green'));
        foreach (array_keys($files) as $path) {
            CLI::write('  ' . CLI::color('Created:', 'green') . ' modules/' . $snake . '/' . $path);
        }
        CLI::write('  ' . CLI::color('Updated:', 'yellow') . ' app/Config/Autoload.php (namespace Modules\\' . $studly . ')');
        CLI::write(CLI::color('MVC structure generated successfully.', 'green'));
        $this->registered($snake, $url);

        return EXIT_SUCCESS;
    }

    /**
     * [snake_case, StudlyCase, Title Case, page address] from the arguments,
     * or the reason they are refused.
     *
     * @return list<string>|string
     */
    protected function module(array $params)
    {
        $input = trim((string) ($params[0] ?? ''));
        if ($input === '') {
            return 'Module name is required. Usage: php spark ' . $this->usage . ' (e.g. php spark ' . $this->name . ' teams)';
        }

        $names = $this->names($input);
        if (is_string($names)) {
            return $names;
        }

        // --url <address>, or --url=<address> (which CLI keeps as the option name).
        $url = CLI::getOption('url');
        foreach (array_keys(CLI::getOptions()) as $option) {
            if (str_starts_with((string) $option, 'url=')) {
                $url = substr((string) $option, 4);
            }
        }
        if ($url !== null && (! is_string($url) || trim($url, " \t/") === '')) {
            return 'The --url option needs an address, e.g. --url our-teams.';
        }
        $url = $url !== null ? strtolower(trim($url, " \t/")) : str_replace('_', '-', $names[0]);
        if (! preg_match(self::URL_PATTERN, $url)) {
            return 'Invalid page address "' . $url . '". Use small letters, digits and single hyphens between words, e.g. our-teams.';
        }

        return [...$names, $url];
    }

    /**
     * [snake_case, StudlyCase, Title Case] for a module name, or the reason
     * the name is refused.
     *
     * @return list<string>|string
     */
    protected function names(string $input)
    {
        if (! preg_match('/^[A-Za-z][A-Za-z0-9_]*$/', $input)) {
            return 'Invalid module name "' . $input . '". Use letters, digits and underscores, starting with a letter (e.g. teams or ContactForm).';
        }

        // ContactForm / contact_form => contact_form; HTMLPage => html_page.
        $snake = strtolower(preg_replace('/(?<=[a-z0-9])(?=[A-Z])|(?<=[A-Z])(?=[A-Z][a-z])/', '_', $input));

        if (! preg_match('/^[a-z][a-z0-9]*(_[a-z0-9]+)*$/', $snake)) {
            return 'Invalid module name "' . $input . '": no double, leading or trailing underscores.';
        }

        if (strlen($snake) > self::MAX_NAME_LENGTH) {
            return 'Module name is too long (max ' . self::MAX_NAME_LENGTH . ' characters as snake_case).';
        }

        $studly = MetaModel::studly($snake);

        if (in_array(strtolower($studly), self::RESERVED_WORDS, true) || preg_match(self::DEVICE_NAMES, $snake)
            || in_array($snake, ['sitepanel', 'pages', 'app', 'config'], true)) {
            return '"' . $input . '" is a reserved word and cannot be used as a module name.';
        }

        return [$snake, $studly, ucwords(str_replace('_', ' ', $snake))];
    }

    /**
     * Why the module can't be created alongside the existing ones, if it can't:
     * its folder or namespace is already taken.
     */
    protected function conflict(string $modules_dir, string $snake, string $studly): ?string
    {
        if (! is_dir($modules_dir)) {
            return 'The modules folder (' . $modules_dir . ') was not found.';
        }

        foreach (scandir($modules_dir) ?: [] as $entry) {
            if ($entry !== '.' && $entry !== '..' && strtolower(str_replace('_', '', $entry)) === strtolower($studly)) {
                return 'Module folder modules/' . $entry . ' already exists. To register a module you copied, run: php spark make:module-route ' . $snake;
            }
        }

        $namespace = $this->registered_namespace($snake, $studly);

        return $namespace !== null ? 'Namespace ' . $namespace . ' is already registered in app/Config/Autoload.php.' : null;
    }

    /**
     * The $psr4 key of this module in app/Config/Autoload.php, if any.
     */
    protected function registered_namespace(string $snake, string $studly): ?string
    {
        foreach ((new Autoload())->psr4 as $namespace => $path) {
            $path = is_array($path) ? implode(' ', $path) : (string) $path;

            if (strcasecmp(trim($namespace, '\\'), 'Modules\\' . $studly) === 0
                || preg_match('#[\\\\/]modules[\\\\/]' . preg_quote($snake, '#') . '[\\\\/]?$#i', $path)) {
                return $namespace;
            }
        }

        return null;
    }

    /**
     * Why the page address or the module's wl_meta_tags row can't be
     * registered, if it can't: the address is reserved, a route of the
     * application or a static page, or either is already in wl_meta_tags.
     */
    protected function url_conflict(string $url, string $snake): ?string
    {
        if (in_array($url, Staticpages::RESERVED_URLS, true) || $url === MetaModel::HOME_URL) {
            return 'The address "' . $url . '" is reserved. Choose another one with --url <address>.';
        }

        try {
            $collection = service('routes')->loadRoutes();

            foreach (['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD', 'CLI', '*'] as $verb) {
                foreach (array_keys($collection->getRoutes($verb, false)) as $route) {
                    $route = strtolower(trim((string) $route, '/'));

                    if ($route === $url || str_starts_with($route, $url . '/')) {
                        return 'The address /' . $url . ' is already used by a route of the application (' . $route . ').';
                    }
                }
            }
        } catch (Throwable $e) {
            return 'Could not read the existing routes to check for conflicts: ' . $e->getMessage();
        }

        $meta = new MetaModel();
        $at   = $meta->get_by_page_url($url);
        $own  = $meta->find('wl_meta_tags', ['entity_type' => $this->page_type($snake), 'entity_id' => 0]);
        $page = (new PagesModel())->friendly_url_exists($url);

        if ($at === false || $own === false || $page === null) {
            return 'Could not read wl_meta_tags / wl_cms_pages (is the database running?).';
        }
        if ($at) {
            return 'The address "' . $url . '" is already registered in wl_meta_tags (meta_id ' . $at['meta_id'] . ', entity_type "' . $at['entity_type'] . '"). Choose another one with --url <address>.';
        }
        if ($own) {
            return 'The module page "' . $this->page_type($snake) . '" is already registered in wl_meta_tags at the address "' . $own['page_url'] . '" (meta_id ' . $own['meta_id'] . ').';
        }
        if ($page) {
            return 'The address "' . $url . '" is used by a static page (Sitepanel > Manage Static Pages).';
        }

        return null;
    }

    /**
     * entity_type of the module's page, as in the reference: '<module>/index'.
     */
    protected function page_type(string $snake): string
    {
        return MetaModel::entity_type($snake);
    }

    /**
     * Add the module's wl_meta_tags row (MetaModel::register_module_page(),
     * one transaction) with its first SEO data, edited later in Sitepanel >
     * Meta Tags.
     *
     * @return string|null NULL when registered, else the reason it was not
     */
    protected function register_page(string $snake, string $title, string $url): ?string
    {
        $site = '';
        try {
            $site = trim((string) service('siteSettings')->get('site_name'));
        } catch (Throwable $e) {
            // The SEO title is then the module title alone.
        }

        return (new MetaModel())->register_module_page($this->page_type($snake), $url, [
            'meta_title'       => $title . ($site !== '' ? ' - ' . $site : ''),
            'meta_description' => '',
            'meta_keyword'     => $title,
        ]);
    }

    protected function registered(string $snake, string $url): void
    {
        CLI::write(CLI::color('Frontend route registered successfully.', 'green')
            . ' (wl_meta_tags: entity_type ' . $this->page_type($snake) . ', page_url ' . $url . ')');
        CLI::write('URL: ' . base_url($url));
        CLI::write('SEO: Sitepanel > Meta Tags (' . $url . ')');
    }

    /**
     * Create a file that must not exist yet (and any missing folders).
     */
    protected function write_new(string $file, string $contents): void
    {
        try {
            token_get_all($contents, TOKEN_PARSE);
        } catch (ParseError $e) {
            throw new \RuntimeException('Generated code for ' . basename($file) . ' is invalid: ' . $e->getMessage());
        }

        $this->make_dir(dirname($file));

        // 'x': fails if the file exists, so nothing is ever overwritten.
        $handle = @fopen($file, 'xb');
        if ($handle === false) {
            throw new \RuntimeException('Cannot create ' . $file . ' (it exists or the folder is not writable).');
        }
        $this->created_files[] = $file;

        $written = fwrite($handle, $contents);
        fclose($handle);

        if ($written !== strlen($contents)) {
            throw new \RuntimeException('Cannot write ' . $file . '.');
        }
    }

    protected function make_dir(string $dir): void
    {
        if (is_dir($dir)) {
            return;
        }

        $this->make_dir(dirname($dir));

        if (! @mkdir($dir, 0755) && ! is_dir($dir)) {
            throw new \RuntimeException('Cannot create folder ' . $dir . '.');
        }
        $this->created_dirs[] = $dir;
    }

    /**
     * Add 'Modules\<Studly>' => ROOTPATH . 'modules/<snake>' after the last
     * module namespace in Config\Autoload::$psr4, aligned like its neighbours,
     * and to this run's autoloader.
     */
    protected function register_namespace(string $snake, string $studly): void
    {
        $file   = APPPATH . 'Config' . DIRECTORY_SEPARATOR . 'Autoload.php';
        $source = @file_get_contents($file);

        if ($source === false) {
            throw new \RuntimeException('Cannot read app/Config/Autoload.php.');
        }

        $start = strpos($source, 'public $psr4');
        $end   = $start === false ? false : strpos($source, '];', $start);

        preg_match_all("/^([ \t]*)('Modules\\\\\\w+')([ \t]*)=>[ \t]*ROOTPATH[ \t]*\\.[ \t]*'modules\\/[\\w\\/-]+',[^\r\n]*/m", $source, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
        $last = end($matches);

        if ($start === false || $end === false || $last === false || $last[0][1] < $start || $last[0][1] > $end) {
            throw new \RuntimeException('Cannot find the module namespaces in $psr4 in app/Config/Autoload.php.');
        }

        $eol    = str_contains($source, "\r\n") ? "\r\n" : "\n";
        $key    = "'Modules\\{$studly}'";
        $width  = max(strlen($last[2][0]) + strlen($last[3][0]), strlen($key) + 1);
        $line   = $last[1][0] . str_pad($key, $width) . "=> ROOTPATH . 'modules/{$snake}',";
        $at     = $last[0][1] + strlen($last[0][0]);
        $update = substr($source, 0, $at) . $eol . $line . substr($source, $at);

        $this->save_config($file, $source, $update);
        service('autoloader')->addNamespace('Modules\\' . $studly, ROOTPATH . 'modules' . DIRECTORY_SEPARATOR . $snake);
    }

    /**
     * Replace a config file's contents after checking the new PHP parses;
     * the original is kept for rollback().
     */
    protected function save_config(string $file, string $original, string $update): void
    {
        $name = 'app/Config/' . basename($file);

        try {
            token_get_all($update, TOKEN_PARSE);
        } catch (ParseError $e) {
            throw new \RuntimeException('Updating ' . $name . ' would break its syntax.');
        }

        if (@file_put_contents($file, $update, LOCK_EX) !== strlen($update)) {
            @file_put_contents($file, $original, LOCK_EX);

            throw new \RuntimeException('Cannot write ' . $name . ' (is it read-only?).');
        }
        $this->changed_files[$file] = $original;
    }

    /**
     * Remove the files and folders this run created (newest first) and
     * restore the config files it changed.
     */
    protected function rollback(): void
    {
        foreach ($this->changed_files as $file => $original) {
            @file_put_contents($file, $original, LOCK_EX);
        }

        foreach (array_reverse($this->created_files) as $file) {
            @unlink($file);
        }

        foreach (array_reverse($this->created_dirs) as $dir) {
            @rmdir($dir);
        }
    }

    protected function fail(string $message): int
    {
        CLI::error('Error: ' . $message);

        return EXIT_ERROR;
    }

    /**
     * Generated files, module-relative path => contents.
     *
     * @return array<string, string>
     */
    protected function files(string $snake, string $studly, string $title, string $url): array
    {
        [$item, $items] = $this->item_names($snake);
        $tokens = ['{snake}' => $snake, '{Studly}' => $studly, '{Title}' => $title, '{url}' => $url, '{item}' => $item, '{items}' => $items];

        return [
            'Controllers/' . $studly . '.php' => strtr($this->with_status(self::CONTROLLER_TEMPLATE), $tokens),
            'Models/' . $studly . 'Model.php' => strtr($this->with_status(self::MODEL_TEMPLATE), $tokens),
            'Views/' . $snake . '.php'        => strtr(self::VIEW_TEMPLATE, $tokens),
            'Views/' . $snake . '_detail.php' => strtr(self::DETAIL_VIEW_TEMPLATE, $tokens),
        ];
    }

    /**
     * Singular and plural of the module name for the model's method names,
     * as the existing models name them (get_faq_by_id() / get_faqs_by_ids(),
     * get_category_by_id() / get_categories_by_ids()):
     * teams => [team, teams], brochure => [brochure, brochures],
     * category => [category, categories], contact_form => [contact_form, contact_forms].
     *
     * @return array{0: string, 1: string}
     */
    protected function item_names(string $snake): array
    {
        if (preg_match('/[^aeiou]ies$/', $snake)) {
            return [substr($snake, 0, -3) . 'y', $snake];
        }
        if (preg_match('/(ss|x|ch|sh)es$/', $snake)) {
            return [substr($snake, 0, -2), $snake];
        }
        if (preg_match('/[^s]s$/', $snake)) {
            return [substr($snake, 0, -1), $snake];
        }
        if (preg_match('/[^aeiou]y$/', $snake)) {
            return [$snake, substr($snake, 0, -1) . 'ies'];
        }
        if (preg_match('/(s|x|ch|sh)$/', $snake)) {
            return [$snake, $snake . 'es'];
        }

        return [$snake, $snake . 's'];
    }

    /**
     * A template for a table with a status '1' / '0' column: its "// [status]"
     * parts kept and its "// [no-status]" parts removed; with --no-status
     * (no such column, like the enquiries) the other way round. The marker
     * lines themselves are always removed.
     */
    protected function with_status(string $template): string
    {
        $status = CLI::getOption('no-status') === null;

        foreach (['status' => $status, 'no-status' => ! $status] as $marker => $keep) {
            $template = (string) preg_replace_callback(
                '/^[ \t]*\/\/ \[' . $marker . '\]\r?\n(.*?)^[ \t]*\/\/ \[\/' . $marker . '\]\r?\n/ms',
                static fn ($m) => $keep ? $m[1] : '',
                $template,
            );
        }

        return $template;
    }

    protected const CONTROLLER_TEMPLATE = <<<'PHP'
        <?php

        namespace Modules\{Studly}\Controllers;

        use App\Controllers\PublicController;
        use Modules\{Studly}\Models\{Studly}Model;

        /**
         * {Title} module, routed through wl_meta_tags (MetaModel::route_for()):
         *
         *   /{url}           index         row entity_type {snake}/index, entity_id 0
         *   /{url}/<slug>    detail/<id>   one row per record: entity_type META_TYPE,
         *                                  entity_id = the record's id (saved with
         *                                  MetaModel::save_entity_page() when the
         *                                  record is added or edited)
         *
         * SEO data is edited in Sitepanel > Meta Tags; the values below are only
         * the defaults for fields left empty there.
         */
        class {Studly} extends PublicController
        {
            /**
             * entity_type of a record's page (detail()).
             */
            public const META_TYPE = '{snake}/detail';

            protected {Studly}Model ${snake}_model;

            protected function init(): void
            {
                $this->{snake}_model = new {Studly}Model();
            }

            public function index(): string
            {
                // [status]
                // Once the table exists:
                // $rows = $this->{snake}_model->get_active_{items}();
                // if ($rows === false) { log_message('error', '{Studly}::index could not load its rows.'); }
                // and pass 'rows' => is_array($rows) ? $rows : [] to the view.
                // [/status]

                return $this->render('Modules\{Studly}\Views\{snake}', [
                    'page_meta'     => ['title' => '{Title}', 'description' => ''],
                    'heading_title' => '{Title}',
                ]);
            }

            /**
             * One record's page. The router passes the entity_id of the record's
             * wl_meta_tags row; a record that doesn't exist (or is inactive) gets
             * the 404 page.
             *
             * @param int|string $id
             */
            public function detail($id = 0): string
            {
                if (preg_match('/^[1-9][0-9]*$/D', (string) $id) !== 1) {
                    $this->not_found_page();
                }

                $row = $this->{snake}_model->get_{item}_by_id((int) $id);
                if ($row === false) {
                    log_message('error', '{Studly}::detail could not load record ' . (int) $id . '.');
                }
                // [status]
                if (! is_array($row) || (string) ($row['status'] ?? '') !== '1') {
                // [/status]
                // [no-status]
                if (! is_array($row)) {
                // [/no-status]
                    $this->not_found_page();
                }

                return $this->render('Modules\{Studly}\Views\{snake}_detail', [
                    'page_meta'     => ['entity' => [self::META_TYPE, (int) $id], 'title' => (string) ($row['title'] ?? '{Title}'), 'description' => ''],
                    'heading_title' => (string) ($row['title'] ?? '{Title}'),
                    'row'           => $row,
                ]);
            }
        }

        PHP;

    protected const MODEL_TEMPLATE = <<<'PHP'
        <?php

        namespace Modules\{Studly}\Models;

        use App\Models\BaseModel;

        /**
         * {Title} ({Studly}Model::TABLE) — the website's /{url} page and the
         * Sitepanel list. Standard model structure (BrochureModel, FaqModel):
         * TABLE / PRIMARY_KEY constants, a list page, get_{item}_by_id(),
        // [status]
         * get_{items}_by_ids(), bulk_update_status(), bulk_delete().
         *
         * Sample methods for a table with a primary key, a 'title' and a
         * status '1' / '0' column: create the table with a migration, then adjust
        // [/status]
        // [no-status]
         * get_{items}_by_ids(), bulk_delete().
         *
         * Sample methods for a table with a primary key and a 'title' column
         * (no status column): create the table with a migration, then adjust
        // [/no-status]
         * TABLE, PRIMARY_KEY, the columns and the order to the real schema.
         *
         * BaseModel returns FALSE when the database is unavailable or a query
         * fails (logged), NULL when no row matches. Other helpers: findCount(),
         * safe_insert(), safe_update(), safe_delete().
         */
        class {Studly}Model extends BaseModel
        {
            public const TABLE       = 'wl_{snake}';
            public const PRIMARY_KEY = '{item}_id';

            /**
             * One Sitepanel list page, newest first; the total is left in
             * $this->total_rec_found.
             *
             * @param string $keyword Part of the title ('' = all)
        // [status]
             * @param string $status  '1' | '0' | '' (all)
        // [/status]
             *
             * @return array|false
             */
        // [status]
            public function get_{items}(int $offset = 0, int $limit = 20, string $keyword = '', string $status = '')
            {
                return $this->paged_rows(self::TABLE, self::PRIMARY_KEY . ' DESC', $offset, $limit, static function ($db) use ($keyword, $status) {
                    $keyword !== '' && $db->like('title', $keyword);
                    $status !== '' && $db->where('status', $status);
                });
            }

            /**
             * Active rows for the website, newest first.
             *
             * @return array|false
             */
            public function get_active_{items}(int $limit = 100)
            {
                if (! $this->db_ready()) {
                    return false;
                }

                return $this->all_rows($this->table(self::TABLE)
                    ->where('status', '1')
                    ->orderBy(self::PRIMARY_KEY, 'DESC')
                    ->limit($limit)
                    ->get());
            }
        // [/status]
        // [no-status]
            public function get_{items}(int $offset = 0, int $limit = 20, string $keyword = '')
            {
                return $this->paged_rows(self::TABLE, self::PRIMARY_KEY . ' DESC', $offset, $limit, static function ($db) use ($keyword) {
                    $keyword !== '' && $db->like('title', $keyword);
                });
            }
        // [/no-status]

            /**
             * @param int|string $id
             *
             * @return array|false|null
             */
            public function get_{item}_by_id($id)
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
            public function get_{items}_by_ids(array $ids)
            {
                return $this->find_many(self::TABLE, self::PRIMARY_KEY, $ids);
            }
        // [status]

            /**
             * @param list<int> $ids
             */
            public function bulk_update_status(array $ids, string $status): bool
            {
                return $this->safe_update_many(self::TABLE, self::PRIMARY_KEY, $ids, ['status' => $status]);
            }
        // [/status]

            /**
             * @param list<int> $ids
             */
            public function bulk_delete(array $ids): bool
            {
                return $this->safe_delete_many(self::TABLE, self::PRIMARY_KEY, $ids);
            }
        }

        PHP;

    protected const VIEW_TEMPLATE = <<<'PHP'
        <?php
        /* {Title} page ({Studly}::index), in the site layout. */
        echo view('top_application');
        ?>
        <section class="section" data-screen-label="{Title}">
        <div class="shell">
        <nav class="page-breadcrumb" aria-label="Breadcrumb" data-reveal>
        <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="<?php echo site_url(); ?>#top"><img src="<?php echo theme_url().'images/icon-home.svg'; ?>" width="14" height="14" alt="" aria-hidden="true" />Home</a></li>
        <li class="breadcrumb-item active" aria-current="page"><?php echo html_escape($heading_title); ?></li>
        </ol>
        </nav>
        <div class="section-head section-head--center" data-reveal>
        <h1 class="h2"><?php echo html_escape($heading_title); ?></h1>
        <p class="lead">The {Title} module is working.</p>
        </div>
        </div>
        </section>
        <?php echo view('bottom_application'); ?>

        PHP;

    protected const DETAIL_VIEW_TEMPLATE = <<<'PHP'
        <?php
        /* One {Title} record ({Studly}::detail), in the site layout. $row = the record. */
        echo view('top_application');
        ?>
        <section class="section" data-screen-label="{Title} Detail">
        <div class="shell">
        <nav class="page-breadcrumb" aria-label="Breadcrumb" data-reveal>
        <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="<?php echo site_url(); ?>#top"><img src="<?php echo theme_url().'images/icon-home.svg'; ?>" width="14" height="14" alt="" aria-hidden="true" />Home</a></li>
        <li class="breadcrumb-item"><a href="<?php echo site_url('{url}'); ?>">{Title}</a></li>
        <li class="breadcrumb-item active" aria-current="page"><?php echo html_escape($heading_title); ?></li>
        </ol>
        </nav>
        <div class="section-head section-head--center" data-reveal>
        <h1 class="h2"><?php echo html_escape($heading_title); ?></h1>
        </div>
        </div>
        </section>
        <?php echo view('bottom_application'); ?>

        PHP;
}
