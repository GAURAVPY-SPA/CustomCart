<?php

use CodeIgniter\Router\RouteCollection;

/**
 * Application routes.
 *
 * The sitepanel's routes live in the module (modules/sitepanel/Config/Routes.php,
 * found by module discovery). The routes of the website modules are all in
 * app/Config/ModuleRoutes.php (Config\Routing::$routeFiles). This file only
 * has the application's own routes; any other URL is a 404.
 *
 * @var RouteCollection $routes
 */

// Uploaded images, served from the root-level uploads/ directory
// (Config\Uploads); old /uploaded_files/<folder>/<file> links redirect there.
$routes->get('uploads/(:segment)/(:segment)', 'Uploads::serve/$1/$2');
// Resized copies made by get_image(), stored in writable/thumb_cache/
// (Config\Uploads::$thumbDirectory, $thumbUrlPrefix).
$routes->get('thumb_cache/(:segment)/(:segment)', 'Uploads::thumb/$1/$2');
$routes->get('uploaded_files/(:segment)/(:segment)', 'Uploads::legacy/$1/$2');

// Verification code image of a public form (Config\Captcha, App\Libraries\Captcha).
$routes->get('captcha/(:segment)', 'Captcha::image/$1');
