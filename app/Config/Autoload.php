<?php

namespace Config;

use CodeIgniter\Config\AutoloadConfig;

/**
 * -------------------------------------------------------------------
 * AUTOLOADER CONFIGURATION
 * -------------------------------------------------------------------
 *
 * This file defines the namespaces and class maps so the Autoloader
 * can find the files as needed.
 *
 * NOTE: If you use an identical key in $psr4 or $classmap, then
 *       the values in this file will overwrite the framework's values.
 *
 * NOTE: This class is required prior to Autoloader instantiation,
 *       and does not extend BaseConfig.
 */
class Autoload extends AutoloadConfig
{
    /**
     * -------------------------------------------------------------------
     * Namespaces
     * -------------------------------------------------------------------
     * This maps the locations of any namespaces in your application to
     * their location on the file system. These are used by the autoloader
     * to locate files the first time they have been instantiated.
     *
     * The 'Config' (APPPATH . 'Config') and 'CodeIgniter' (SYSTEMPATH) are
     * already mapped for you.
     *
     * You may change the name of the 'App' namespace if you wish,
     * but this should be done prior to creating any namespaced classes,
     * else you will need to modify all of those classes for this to work.
     *
     * @var array<string, list<string>|string>
     */
    public $psr4 = [
        APP_NAMESPACE => APPPATH,

        // HMVC modules (same folders as the CodeIgniter 3 modules/ directory).
        // Each module keeps its own Config/ (Routes, Services), Controllers/,
        // Models/, Libraries/, Helpers/ and Views/.
        'Modules\Sitepanel'         => ROOTPATH . 'modules/sitepanel',
        'Modules\Testimonials'      => ROOTPATH . 'modules/testimonials',
        'Modules\Pages'             => ROOTPATH . 'modules/pages',
        'Modules\Home'              => ROOTPATH . 'modules/home',
        // Online store
        'Modules\Products'          => ROOTPATH . 'modules/products',
        'Modules\Category'          => ROOTPATH . 'modules/category',
        'Modules\Brand'             => ROOTPATH . 'modules/brand',
        'Modules\Color'             => ROOTPATH . 'modules/color',
        'Modules\Size'              => ROOTPATH . 'modules/size',
        'Modules\Material'          => ROOTPATH . 'modules/material',
        'Modules\Lookup'            => ROOTPATH . 'modules/lookup',
        'Modules\Review'           => ROOTPATH . 'modules/review',
        'Modules\Cart'              => ROOTPATH . 'modules/cart',
        'Modules\Users'             => ROOTPATH . 'modules/users',
        'Modules\Members'           => ROOTPATH . 'modules/members',
        'Modules\Gallery'           => ROOTPATH . 'modules/gallery',
        'Modules\Video'             => ROOTPATH . 'modules/video',       
        'Modules\News'              => ROOTPATH . 'modules/news',
        'Modules\Team'              => ROOTPATH . 'modules/team',
        'Modules\Certificates'      => ROOTPATH . 'modules/certificates',
    ];

    /**
     * -------------------------------------------------------------------
     * Class Map
     * -------------------------------------------------------------------
     * The class map provides a map of class names and their exact
     * location on the drive. Classes loaded in this manner will have
     * slightly faster performance because they will not have to be
     * searched for within one or more directories as they would if they
     * were being autoloaded through a namespace.
     *
     * Prototype:
     *   $classmap = [
     *       'MyClass'   => '/path/to/class/file.php'
     *   ];
     *
     * @var array<string, string>
     */
    public $classmap = [];

    /**
     * -------------------------------------------------------------------
     * Files
     * -------------------------------------------------------------------
     * The files array provides a list of paths to __non-class__ files
     * that will be autoloaded. This can be useful for bootstrap operations
     * or for loading functions.
     *
     * Prototype:
     *   $files = [
     *       '/path/to/my/file.php',
     *   ];
     *
     * @var list<string>
     */
    public $files = [];

    /**
     * -------------------------------------------------------------------
     * Helpers
     * -------------------------------------------------------------------
     * Prototype:
     *   $helpers = [
     *       'form',
     *   ];
     *
     * @var list<string>
     */
    // 'app' before 'form' and 'text': its CodeIgniter 3-compatible
    // character_limiter(), set_checkbox(), set_radio() and form_error() are
    // defined first, so the framework helpers keep them. Module helpers
    // (blog, sitepanel, admin) are loaded by the controllers that use them.
    public $helpers = ['url', 'html', 'app', 'form', 'text', 'utils'];
}
