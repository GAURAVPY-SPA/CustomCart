<?php

/**
 * Routes of the website modules (every module in modules/ except sitepanel).
 * Loaded once through Config\Routing::$routeFiles, after app/Config/Routes.php.
 * The sitepanel keeps its own routes in modules/sitepanel/Config/Routes.php
 * (found by module discovery).
 *
 * The website's page and module addresses are not listed here: they are
 * rows of wl_meta_tags, the URL registry (CodeIgniter 3: MY_Router::_db_routes).
 * Each request's address is looked up once in wl_meta_tags.page_url (the
 * site root as 'home') and routed by its row (MetaModel::route_for()):
 *   <module>/<method>      a module page, e.g. teams/index =>
 *                          Modules\Teams\Controllers\Teams::index (home/index,
 *                          testimonials/index...; registered by
 *                          php spark make:module / make:module-route)
 *   pages/index/<address>  a static page (entity_id = page_id)
 *   category/index         a store category (entity_id = category_id; page_url
 *                          <category>[/<sub>[/<sub-sub>]], at the site root)
 *   products/detail        a product (entity_id = products_id; page_url <slug>,
 *                          at the site root)
 * page_url is unique, so one address is one page: a product and a category
 * can't share an address (the Sitepanel refuses the second one).
 * Rows of other types only hold SEO data. The sitepanel, uploaded files and
 * CLI commands never query the table; an unknown address gets the 404 page.
 *
 * A module needs no section here: its page is its wl_meta_tags row. Add
 * one only for routes that don't fit the registry.
 *
 * @var CodeIgniter\Router\RouteCollection $routes
 */

// --------------------------------------------------------------------
// The designed 404 page for every unknown URL (CodeIgniter 3: 404_override).
// --------------------------------------------------------------------
$routes->set404Override('Modules\Pages\Controllers\Pages::not_found');

// --------------------------------------------------------------------
// Website pages and modules routed through wl_meta_tags.
// --------------------------------------------------------------------
if (! is_cli()) {
    $meta_path = trim((string) preg_replace('#/+#', '/', service('request')->getPath()), '/');
    $meta_skip = in_array(explode('/', $meta_path)[0], Modules\Sitepanel\Models\MetaModel::RESERVED_SEGMENTS, true);

    if (! $meta_skip && ! isset($routes->getRoutes('GET')[$meta_path === '' ? '/' : $meta_path])) {
        $meta_row   = (new Modules\Sitepanel\Models\MetaModel())->get_route($meta_path === '' ? Modules\Sitepanel\Models\MetaModel::HOME_URL : $meta_path);
        $meta_route = is_array($meta_row) ? Modules\Sitepanel\Models\MetaModel::route_for($meta_row) : null;

        if ($meta_route !== null) {
            $routes->match($meta_route[1], $meta_path === '' ? '/' : $meta_path, $meta_route[0]);
        }
    }
    unset($meta_path, $meta_skip, $meta_row, $meta_route);
}
