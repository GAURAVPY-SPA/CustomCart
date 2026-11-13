<?php

namespace Modules\News\Controllers;

use App\Controllers\PublicController;
use App\Exceptions\HaltException;
use App\Libraries\HtmlSanitizer;
use Modules\News\Models\NewsModel;
use Modules\Sitepanel\Models\MetaModel;

/**
 * News & Events (design: html/news-and-events.htm, html/news-detail.htm),
 * routed through wl_meta_tags (MetaModel::route_for()):
 *
 *   /news-and-events          index         row entity_type news/index, entity_id 0
 *   /news-and-events/<slug>   detail/<id>   one row per item: entity_type META_TYPE,
 *                                           entity_id = news_id (saved with
 *                                           MetaModel::save_entity_page() in
 *                                           Sitepanel > Manage News & Events)
 *
 * The listing shows the active items as the design's news cards, PER_PAGE a
 * page (?page=); an item's page shows its type (News / Event), date, title,
 * image, content (HtmlSanitizer) and Recent News (the next active items).
 * Inactive items get the 404 page.
 *
 * SEO data is edited with the item (or in Sitepanel > Meta Tags); the values
 * below are only the defaults for fields left empty there.
 */
class News extends PublicController
{
    public const INDEX_TYPE = 'news/index';
    public const META_TYPE  = 'news/detail';

    public const IMAGE_FOLDER = 'news';

    /**
     * Item types (wl_news.news_category), as shown on the cards.
     */
    public const CATEGORIES = ['News', 'Event'];

    /**
     * Items per listing page (the design shows three cards a row).
     */
    public const PER_PAGE = 12;

    /**
     * Recent News beside an item (the design's sidebar of three).
     */
    public const RECENT_COUNT = 3;

    protected NewsModel $news_model;

    protected function init(): void
    {
        $this->news_model = new NewsModel();
    }

    /**
     * An item as the cards and the sidebar show it: url, image / thumb URLs ('' = none), time.
     */
    public static function present(array $row): array
    {
        return $row + [
            'url'       => site_url((string) $row['page_url']),
            'time'      => (int) strtotime((string) $row['publish_date']),
            'type'      => mb_strtoupper((string) $row['news_category'] !== '' ? (string) $row['news_category'] : 'News'),
            'image_url' => get_image(self::IMAGE_FOLDER, $row['news_image'], 800, 600, 'C'),
            'thumb_url' => get_image(self::IMAGE_FOLDER, $row['news_image'], 200, 200, 'C'),
        ];
    }

    public function index(): string
    {
        $rows   = $this->news_model->get_active_news(self::META_TYPE);
        $failed = $rows === false;
        if ($failed) {
            log_message('error', 'News::index could not load the news.');
        }
        $rows    = $failed ? [] : $rows;
        $current = max(1, (int) $this->request->getGet('page'));
        if ($current > 1 && ($current - 1) * self::PER_PAGE >= count($rows)) {
            $this->not_found_page(); // past the last page
        }

        return $this->render('Modules\News\Views\news_listing', [
            'page_meta'   => ['title' => 'News & Events' . $this->title_suffix(), 'description' => 'News, events and product updates from CustomCart.'],
            'news'        => array_map([self::class, 'present'], array_slice($rows, ($current - 1) * self::PER_PAGE, self::PER_PAGE)),
            'unavailable' => $failed,
            'pager'       => ['page' => $current, 'pages' => (int) ceil(count($rows) / self::PER_PAGE), 'url' => site_url($this->list_url())],
        ]);
    }

    /**
     * One item's page. The router passes the entity_id of its wl_meta_tags row.
     *
     * @param int|string $id
     */
    public function detail($id = 0): string
    {
        if (preg_match('/^[1-9][0-9]*$/D', (string) $id) !== 1) {
            $this->not_found_page();
        }
        $row = $this->news_model->get_news_by_id((int) $id);
        if ($row === false) {
            log_message('error', 'News::detail could not load item ' . (int) $id . '.');

            throw HaltException::error('The requested article is temporarily unavailable.', 503);
        }
        if (! is_array($row) || (string) $row['status'] !== '1') {
            $this->not_found_page();
        }

        $recent = $this->news_model->get_active_news(self::META_TYPE, self::RECENT_COUNT, (int) $id);

        return $this->render('Modules\News\Views\news_detail', [
            'page_meta' => [
                'entity'      => [self::META_TYPE, (int) $id],
                'title'       => $row['news_title'] . $this->title_suffix(),
                'description' => mb_substr((string) $row['short_description'], 0, 160),
            ],
            'item'      => $row + [
                'type'      => mb_strtoupper((string) $row['news_category'] !== '' ? (string) $row['news_category'] : 'News'),
                'time'      => (int) strtotime((string) $row['publish_date']),
                'image_url' => get_image(self::IMAGE_FOLDER, $row['news_image'], 1200, 900),
            ],
            'content'   => (new HtmlSanitizer())->clean((string) $row['description']),
            'recent'    => array_map([self::class, 'present'], is_array($recent) ? $recent : []),
            'list_url'  => site_url($this->list_url()),
        ]);
    }

    /**
     * Address of the listing (its wl_meta_tags row, news/index).
     */
    protected function list_url(): string
    {
        $route = (new MetaModel())->get_route_of(self::INDEX_TYPE);

        return is_array($route) ? (string) $route['page_url'] : '';
    }

    protected function title_suffix(): string
    {
        return ' | ' . $this->site_settings->get('site_name');
    }
}
