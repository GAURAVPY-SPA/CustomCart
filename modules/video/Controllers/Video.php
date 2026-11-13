<?php

namespace Modules\Video\Controllers;

use App\Controllers\PublicController;
use App\Libraries\ImageUpload;
use Modules\Video\Models\VideoModel;

/**
 * Videos Gallery page (design: html/videos-gallery.htm), routed through
 * wl_meta_tags (MetaModel::route_for()):
 *
 *   /videos-gallery    index    row entity_type video/index, entity_id 0
 *
 * The active videos of Sitepanel > Manage Video Gallery in display order,
 * as cards (thumbnail, title, "category · duration"); a card plays its video
 * in the video modal (assets/designer/resources/js/gallery.js). Videos have
 * no pages of their own.
 *
 * SEO data is edited in Sitepanel > Meta Tags; the values below are only
 * the defaults for fields left empty there.
 */
class Video extends PublicController
{
    protected VideoModel $video_model;

    protected function init(): void
    {
        $this->video_model = new VideoModel();
    }

    public function index(): string
    {
        $rows = $this->video_model->get_active_videos();
        if ($rows === false) {
            log_message('error', 'Video::index could not load the videos.');
        }

        // The uploaded thumbnail, else YouTube's; a video without either, or with a link
        // that is no longer a YouTube / Vimeo video, is left out.
        $images = new ImageUpload();
        $videos = [];
        foreach (is_array($rows) ? $rows : [] as $row) {
            $embed = VideoModel::embed_url((string) $row['video_link']);
            $file  = $images->path(\Modules\Sitepanel\Controllers\Video::IMAGE_FOLDER, $row['video_image']);
            $thumb = $file !== '' ? get_image(\Modules\Sitepanel\Controllers\Video::IMAGE_FOLDER, $row['video_image'], 600, 600, 'C') : VideoModel::thumbnail_url((string) $row['video_link']);
            if ($embed !== '' && $thumb !== '') {
                $videos[] = [
                    'title' => (string) $row['video_title'],
                    'meta'  => implode(' · ', array_filter([(string) $row['video_category'], (string) $row['video_duration']], 'strlen')),
                    'embed' => $embed,
                    'link'  => (string) $row['video_link'],
                    'thumb' => $thumb,
                ];
            }
        }

        return $this->render('Modules\Video\Views\video', [
            'page_meta'    => ['title' => 'Video Gallery', 'description' => 'Videos of CustomCart products and events.'],
            'videos'       => $videos,
            'unavailable'  => $rows === false,
        ]);
    }
}
