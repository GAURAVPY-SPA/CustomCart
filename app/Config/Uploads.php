<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Storage of the images uploaded through the sitepanel (and the public
 * testimonial form), used by App\Libraries\ImageUpload and served by
 * App\Controllers\Uploads.
 *
 * Files live in one directory at the project root, uploads/<folder>/, next
 * to app/, modules/, public/ and writable/ — outside the web root, so the web
 * server never serves or executes anything from it directly. Images are
 * delivered by the /uploads/<folder>/<file> route instead.
 *
 * The database keeps only the file name (e.g. wl_blogs.image =
 * '779aeb07c4fe08d5119ab5362682b50d.jpg'); the folder follows from the record
 * type, and the relative path is $urlPrefix . <folder> . '/' . <file>.
 *
 * Every value can be set in .env as uploads.<property>, e.g.
 *   uploads.directory = /home/account/site/uploads/
 */
class Uploads extends BaseConfig
{
    /**
     * Absolute path of the upload directory, with a trailing slash.
     */
    public string $directory = ROOTPATH . 'uploads/';

    /**
     * Relative path (and public URL path) of the directory, e.g. 'uploads/'
     * gives 'uploads/blog/<file>' and https://example.com/uploads/blog/<file>.
     */
    public string $urlPrefix = 'uploads/';

    /**
     * Upload folders => the table columns that store their file names.
     * A file is only deleted when no row of these columns still names it.
     *
     * @var array<string, list<array{0: string, 1: string}>>
     */
    public array $folders = [
        'banner'        => [['wl_banners', 'banner_image']],
        'header_images' => [['wl_header_images', 'header_image']],
        'testimonial'   => [['wl_testimonial', 'photo']],
        'gallery'       => [['wl_gallery', 'gallery_image']],
        'video'         => [['wl_video', 'video_image']],
        'team'          => [['wl_team', 'photo']],
        'certificates'  => [['wl_certificates', 'cert_image']],
        'brochures'     => [['wl_certificates', 'cert_file']],
        'events'        => [['wl_events', 'event_image']],
        'news'          => [['wl_news', 'news_image']],
        // Online store; an order line keeps its product photo after the product changes.
        'category' => [['wl_categories', 'category_image']],
        'brand'    => [['wl_brands', 'brand_image']],
        'products' => [['wl_products_media', 'media'], ['wl_orders_products', 'product_image']],
        // Images added with the sitepanel CKEditor image dialog (AdminController::upload_image).
        // They are named inside the HTML, not in a column, and are never deleted automatically.
        'editor' => [],
        // Logo, favicon and social image (Admin Settings; read only in this version).
        'settings' => [['tbl_site_settings', 'setting_value']],
    ];

    /**
     * Accepted image extensions => MIME types detected from the file content
     * (CodeIgniter 3 config/mimes.php).
     *
     * @var array<string, list<string>>
     */
    public array $mimes = [
        'gif'  => ['image/gif'],
        'jpeg' => ['image/jpeg', 'image/pjpeg'],
        'jpg'  => ['image/jpeg', 'image/pjpeg'],
        'png'  => ['image/png', 'image/x-png'],
        'webp' => ['image/webp'],
    ];

    /**
     * Largest accepted upload, in kilobytes.
     */
    public int $maxKb = 2048;

    /**
     * Folders holding documents instead of images (App\Libraries\DocumentUpload):
     * saved and served with $documentMimes and $documentMaxKb.
     *
     * @var list<string>
     */
    public array $documentFolders = ['brochures'];

    /**
     * Accepted document extensions and the MIME types detected from their content.
     * A .docx is a ZIP archive: some systems detect it as application/zip, so
     * DocumentUpload also checks that it holds word/document.xml.
     *
     * @var array<string, list<string>>
     */
    public array $documentMimes = [
        'pdf'  => ['application/pdf'],
        'doc'  => ['application/msword', 'application/CDFV2', 'application/x-ole-storage'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
    ];

    /**
     * Largest accepted document, in kilobytes.
     */
    public int $documentMaxKb = 10240;

    /**
     * Browser cache lifetime of a served image, in seconds. Stored names are
     * random and a replacement gets a new name, so a long lifetime is safe.
     */
    public int $cacheSeconds = 2592000;

    /**
     * Thumbnail cache of get_image(): resized copies of the uploaded images,
     * <thumbDirectory><folder>/thumb_<width>_<height>_<file>, made on first
     * request. It lives in writable/, which is never served directly; the
     * /<thumbUrlPrefix><folder>/<file> route (App\Controllers\Uploads::thumb())
     * delivers the copies. Originals are never changed. Sitepanel > Clear
     * Cache empties it (keeping the folder and its index.html); anything
     * removed is made again when next shown.
     */
    public string $thumbDirectory = WRITEPATH . 'thumb_cache/';

    public string $thumbUrlPrefix = 'thumb_cache/';

    /**
     * Largest width or height a thumbnail may be requested at, in pixels.
     */
    public int $thumbMaxSide = 4000;

    /**
     * A thumbnail no page has shown for this many days is removed by
     * `php spark cache:cleanup-thumbnails` (App\Libraries\ThumbnailCache);
     * it is made again if a page asks for it later.
     */
    public int $thumbExpireDays = 30;

    /**
     * get_image() records that a cached thumbnail is still shown by setting
     * its modification time, at most once per this many seconds per file
     * (so most page views write nothing).
     */
    public int $thumbTouchSeconds = 86400;
}
