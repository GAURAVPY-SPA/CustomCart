<?php

namespace App\Commands;

use App\Controllers\PublicController;
use CodeIgniter\CLI\CLI;
use FilesystemIterator;
use Modules\Sitepanel\Models\MetaModel;
use ParseError;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;

/**
 * Registers the page of a module made by copying another one:
 *
 *   php spark make:module-route teams [--url our-teams]
 *
 * After the copied folder is renamed (modules/teams) and its classes,
 * namespaces and view references are updated, the command checks the module
 * before anything is written:
 *
 *  - modules/<name>/Controllers, Models and Views exist;
 *  - every PHP file parses, and each class file under Controllers/ or Models/
 *    declares the namespace of its folder (Modules\Teams\Controllers...);
 *  - Controllers/Teams.php is Modules\Teams\Controllers\Teams, a website
 *    controller (App\Controllers\PublicController) with a public index().
 *
 * Then it registers the module's namespace in app/Config/Autoload.php if it
 * is not there yet, and its page in wl_meta_tags (entity_type teams/index,
 * page_url teams), exactly as php spark make:module does. Nothing is changed
 * when a check fails or the address / row is already registered.
 *
 * References to another module's namespace (Modules\Testimonials\...) left
 * from the copy are reported as warnings: they may be intended (a shared
 * model), but are usually a missed rename.
 */
class MakeModuleRoute extends MakeModule
{
    protected $name        = 'make:module-route';
    protected $description = 'Checks an existing (e.g. copied) module in modules/ and registers its page in wl_meta_tags.';
    protected $usage       = 'make:module-route <name> [--url <address>]';
    protected $arguments   = [
        'name' => 'Module folder name in modules/, e.g. teams or contact_form.',
    ];

    /**
     * Namespaces a module may use besides its own.
     */
    protected const SHARED_MODULES = ['Sitepanel'];

    public function run(array $params)
    {
        $module = $this->module($params);
        if (is_string($module)) {
            return $this->fail($module);
        }
        [$snake, $studly, , $url] = $module;

        $module_dir = ROOTPATH . 'modules' . DIRECTORY_SEPARATOR . $snake;
        $warnings   = [];
        $error      = $this->check_module($module_dir, $snake, $studly, $warnings) ?? $this->url_conflict($url, $snake);
        if ($error !== null) {
            return $this->fail($error);
        }

        $namespace_added = false;
        try {
            if ($this->registered_namespace($snake, $studly) === null) {
                $this->register_namespace($snake, $studly);
                $namespace_added = true;
            }

            $error = $this->check_controller($snake, $studly);
            if ($error === null) {
                $error = $this->register_page($snake, ucwords(str_replace('_', ' ', $snake)), $url);
            }
            if ($error !== null) {
                throw new \RuntimeException($error);
            }
        } catch (Throwable $e) {
            $this->rollback();

            return $this->fail(rtrim($e->getMessage(), '.') . '. Nothing was changed.');
        }

        foreach ($warnings as $warning) {
            CLI::write(CLI::color('Warning:', 'yellow') . ' ' . $warning);
        }
        CLI::write(CLI::color('Module ' . $snake . ' checked successfully.', 'green')
            . ' (Modules\\' . $studly . '\Controllers\\' . $studly . '::index)');
        if ($namespace_added) {
            CLI::write('  ' . CLI::color('Updated:', 'yellow') . ' app/Config/Autoload.php (namespace Modules\\' . $studly . ')');
        }
        $this->registered($snake, $url);

        return EXIT_SUCCESS;
    }

    /**
     * Why the module folder doesn't follow the module architecture, if it
     * doesn't; references to other modules are added to $warnings.
     *
     * @param list<string> $warnings
     */
    protected function check_module(string $module_dir, string $snake, string $studly, array &$warnings): ?string
    {
        if (! is_dir($module_dir)) {
            return 'Module folder modules/' . $snake . ' was not found. To generate a new module, run: php spark make:module ' . $snake;
        }
        foreach (['Controllers', 'Models', 'Views'] as $folder) {
            if (! is_dir($module_dir . DIRECTORY_SEPARATOR . $folder)) {
                return 'modules/' . $snake . '/' . $folder . '/ is missing (a module has Controllers/, Models/ and Views/).';
            }
        }
        if (! is_file($module_dir . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR . $studly . '.php')) {
            return 'modules/' . $snake . '/Controllers/' . $studly . '.php is missing (the module controller is named after the module).';
        }

        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($module_dir, FilesystemIterator::SKIP_DOTS));
        foreach ($files as $file) {
            if (strtolower($file->getExtension()) !== 'php') {
                continue;
            }
            $relative = str_replace(DIRECTORY_SEPARATOR, '/', substr($file->getPathname(), strlen($module_dir) + 1));
            $source   = (string) file_get_contents($file->getPathname());

            try {
                token_get_all($source, TOKEN_PARSE);
            } catch (ParseError $e) {
                return 'modules/' . $snake . '/' . $relative . ' has a syntax error on line ' . $e->getLine() . ': ' . $e->getMessage();
            }

            // Class files: PSR-4, the namespace follows the folder.
            if (! str_starts_with($relative, 'Views/') && ! str_starts_with($relative, 'Config/')) {
                $expected = 'Modules\\' . $studly . '\\' . str_replace('/', '\\', dirname($relative));
                if (preg_match('/^\s*namespace\s+([^;]+);/m', $source, $m) && trim($m[1]) !== $expected) {
                    return 'modules/' . $snake . '/' . $relative . ' declares namespace ' . trim($m[1]) . '; it must be ' . $expected . '.';
                }
            }

            if (preg_match_all('/Modules\\\\{1,2}(\w+)\\\\/', $source, $refs)) {
                foreach (array_unique($refs[1]) as $other) {
                    if ($other !== $studly && ! in_array($other, self::SHARED_MODULES, true)) {
                        $warnings[] = 'modules/' . $snake . '/' . $relative . ' refers to Modules\\' . $other . ' (left from the copy?).';
                    }
                }
            }
        }

        return null;
    }

    /**
     * Why the module controller can't be routed, if it can't: the class
     * must load, be a website controller and have a public index(), as the
     * router resolves it (MetaModel::module_route()).
     */
    protected function check_controller(string $snake, string $studly): ?string
    {
        $class = 'Modules\\' . $studly . '\Controllers\\' . $studly;

        try {
            if (! class_exists($class)) {
                return 'Class ' . $class . ' was not found in modules/' . $snake . '/Controllers/' . $studly . '.php (check its class name and namespace).';
            }
        } catch (Throwable $e) {
            return 'Class ' . $class . ' could not be loaded: ' . $e->getMessage();
        }
        if (! is_subclass_of($class, PublicController::class)) {
            return $class . ' must extend App\Controllers\PublicController (website pages: shared layout data, maintenance mode).';
        }
        if (MetaModel::module_route($this->page_type($snake)) === null) {
            return $class . ' has no public index() method of its own.';
        }

        return null;
    }
}
