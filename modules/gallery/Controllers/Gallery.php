<?php

namespace Modules\Gallery\Controllers;

use App\Controllers\PublicController;
use App\Libraries\ImageUpload;
use Modules\Gallery\Models\GalleryModel;

/**
 * Images Gallery page (design: html/images-gallery.htm), routed through
 * wl_meta_tags (MetaModel::route_for()):
 *
 *   /images-gallery    index    row entity_type gallery/index, entity_id 0
 *
 * The active photos of Sitepanel > Manage Images Gallery in display order,
 * as a masonry grid; a photo opens larger in the lightbox
 * (assets/designer/resources/js/gallery.js). Photos have no pages of their own.
 *
 * SEO data is edited in Sitepanel > Meta Tags; the values below are only
 * the defaults for fields left empty there.
 */
class Gallery extends PublicController
{
    protected GalleryModel $gallery_model;

    protected function init(): void
    {
        $this->gallery_model = new GalleryModel();
    }

    public function index(): string
    {
        $rows = $this->gallery_model->get_active_galleries();
        if ($rows === false) {
            log_message('error', 'Gallery::index could not load the photos.');
        }

        // Photos whose file is missing are left out (never a broken image).
        $images = new ImageUpload();
        $photos = [];
        foreach (is_array($rows) ? $rows : [] as $row) {
            $path = $images->absolute(\Modules\Sitepanel\Controllers\Gallery::IMAGE_FOLDER, $row['gallery_image']);
            $size = $path !== '' ? @getimagesize($path) : false;
            if ($size !== false) {
                $photos[] = [
                    'title'  => (string) $row['gallery_title'],
                    // Square grid tile (object-fit cover); the lightbox shows the same image whole.
                    'src'    => get_image(\Modules\Sitepanel\Controllers\Gallery::IMAGE_FOLDER, $row['gallery_image'], 1200, 1200),
                    'full'   => base_url($images->path(\Modules\Sitepanel\Controllers\Gallery::IMAGE_FOLDER, $row['gallery_image'])),
                    'width'  => (int) $size[0],
                    'height' => (int) $size[1],
                ];
            }
        }

        return $this->render('Modules\Gallery\Views\gallery', [
            'page_meta'    => ['title' => 'Images Gallery', 'description' => 'Photos of CustomCart products, facilities and events.'],
            'photos'       => $photos,
            'unavailable'  => $rows === false,
        ]);
    }
}
