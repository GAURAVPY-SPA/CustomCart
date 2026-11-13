<?php

namespace Modules\Sitepanel\Controllers;

use App\Controllers\AdminController;
use App\Libraries\FormValidation;
use App\Libraries\HtmlSanitizer;
use App\Libraries\ImageUpload;
use DateTime;
use Modules\News\Controllers\News as NewsPage;
use Modules\News\Models\NewsModel;
use Modules\Sitepanel\Libraries\AdminAccess;
use Modules\Sitepanel\Models\MetaModel;

/**
 * Sitepanel > Other Management > Manage News & Events (wl_news), the items
 * of the website's /news-and-events page and their own pages (module news;
 * design: html/news-and-events.htm, html/news-detail.htm).
 *
 * List with title / category search, status filter and display order; add /
 * edit (Title, Type (News / Event) and Publication date required; Short description,
 * Content — the full editor, cleaned by HtmlSanitizer —, Image, Display
 * order, Status and SEO); activate, deactivate and delete, one row or the
 * selected rows. Display order 0 = by date (newest first).
 *
 * Each item's page address is its own wl_meta_tags row (entity_type
 * NewsPage::META_TYPE, entity_id = news_id, page_url '<news page>/<slug>',
 * saved with MetaModel::save_entity_page()) with its SEO data; the row is
 * removed with the item.
 *
 *   index          news/view_news_list
 *   add, edit/<id> news/view_news_form
 *   status/<id>    POST, AdminController::update_status()
 *   delete/<id>    POST, also removes the image and the page's wl_meta_tags row
 *   bulk           POST, activate / deactivate / delete the selected rows
 *   sort           POST ord[<news_id>] = display order, rows of the current page
 *   upload_image   POST, the editor's image upload (AdminController::upload_image())
 */
class News extends AdminController
{
    /**
     * Image size hint (the design's card / article images are landscape, about 4:3).
     */
    public const BEST_SIZE = '1200 × 896 pixels (landscape)';

    protected string $base           = 'sitepanel/news';
    protected string $item           = 'News item';
    protected string $menu_highlight = 'news';

    protected NewsModel $news_model;
    protected MetaModel $meta_model;
    protected ImageUpload $image_upload;

    protected function init(): void
    {
        $this->news_model   = new NewsModel();
        $this->meta_model   = new MetaModel();
        $this->image_upload = new ImageUpload();
    }

    public function index(): string
    {
        $keyword = mb_substr(trim((string) $this->request->getGet('keyword')), 0, 80);
        $status  = $this->status_param((string) $this->request->getGet('status'));
        $page    = $this->current_page();
        $res     = $this->news_model->get_news(($page - 1) * self::PAGESIZE, self::PAGESIZE, $keyword, $status);

        $data['heading_title'] = 'Manage News & Events';
        $data['breadcrumbs']   = ['News & Events' => null];
        $data['res']           = $res;
        $data['total']         = $this->news_model->total_rec_found;
        $data['page']          = $page;
        $data['page_links']    = admin_pagination($this->base, $data['total'], self::PAGESIZE);
        $data['keyword']       = $keyword;
        $data['status']        = $status;
        $data['page_url']      = $this->list_page_url();

        return $this->render('news/view_news_list', $data);
    }

    public function add(): string
    {
        $this->require_privilege(AdminAccess::PRIV_ADD);
        $data['errors'] = [];

        if ($this->request->getMethod() === 'POST') {
            $data['errors'] = $this->save(null);
            if (empty($data['errors'])) {
                $this->session->setFlashdata('sitepanel_success', 'News item added.');
                redirect_now($this->base);
            }
        }

        $data['heading_title'] = 'Add News';
        $data['breadcrumbs']   = ['News & Events' => site_url($this->base), 'Add News' => null];
        $data['res']           = array_fill_keys([
            'news_title', 'friendly_url', 'news_category', 'short_description', 'description', 'meta_title', 'meta_keyword', 'meta_description',
        ], '') + ['publish_date' => date('Y-m-d'), 'sort_order' => '0', 'status' => '1'];
        $data['image']    = '';
        $data['meta']     = null;
        $data['page_url'] = $this->list_page_url();

        return $this->render('news/view_news_form', $data);
    }

    /**
     * @param int|string $id
     */
    public function edit($id = 0): string
    {
        $res = $this->record_or_back($this->news_model->get_news_by_id($id));
        $this->require_privilege(AdminAccess::PRIV_EDIT);
        $meta           = $this->meta_model->get_entity_meta(NewsPage::META_TYPE, $res['news_id']);
        $data['errors'] = [];

        if ($this->request->getMethod() === 'POST') {
            $data['errors'] = $this->save($res, $meta);
            if (empty($data['errors'])) {
                $this->session->setFlashdata('sitepanel_success', 'News item updated.');
                redirect_now($this->base);
            }
        }

        foreach (['meta_title', 'meta_keyword', 'meta_description'] as $field) {
            $res[$field] = is_array($meta) ? (string) $meta[$field] : '';
        }
        $data['heading_title'] = 'Edit News';
        $data['breadcrumbs']   = ['News & Events' => site_url($this->base), 'Edit News' => null];
        $data['res']           = $res;
        $data['image']         = $this->image_upload->path(NewsPage::IMAGE_FOLDER, $res['news_image']);
        $data['meta']          = $meta;
        $data['page_url']      = $this->list_page_url();

        return $this->render('news/view_news_form', $data);
    }

    /**
     * @param int|string $id
     */
    public function status($id = 0): void
    {
        $this->update_status(NewsModel::TABLE, NewsModel::PRIMARY_KEY, $id);
    }

    /**
     * @param int|string $id
     */
    public function delete($id = 0): void
    {
        $this->require_post();
        $this->require_privilege(AdminAccess::PRIV_DELETE);
        $row = $this->record_or_back($this->news_model->get_news_by_id($id));

        if (! $this->meta_model->delete_entity_meta(NewsPage::META_TYPE, $row['news_id'])) {
            $this->session->setFlashdata('sitepanel_error', 'The item\'s page address could not be removed, so the item was not deleted. Please try again.');
            $this->back($this->list_query());
        }

        $this->delete_record(NewsModel::TABLE, NewsModel::PRIMARY_KEY, $id, 'news_image', NewsPage::IMAGE_FOLDER);
    }

    /**
     * POST: activate / deactivate / delete the selected items. As in delete(),
     * each one's wl_meta_tags row is removed first.
     */
    public function bulk(): void
    {
        $query = $this->list_query();

        if ($this->request->getPost('bulk_action') !== 'delete') {
            $this->process_bulk_action($this->news_model, 'get_news_by_ids', 'News item', $query);
        }

        $this->bulk_delete_checked($this->news_model, 'get_news_by_ids', 'News item', $query, function (array $rows) {
            foreach ($rows as $row) {
                if (! $this->meta_model->delete_entity_meta(NewsPage::META_TYPE, $row['news_id'])) {
                    return 'The page address of "' . $row['news_title'] . '" could not be removed, so nothing was deleted. Please try again.';
                }
            }

            return null;
        }, 'news_image', NewsPage::IMAGE_FOLDER);
    }

    /**
     * POST ord[<news_id>] => display order (0-9999) for the items of one list page.
     */
    public function sort(): void
    {
        $this->require_post();
        $this->require_privilege(AdminAccess::PRIV_EDIT);
        $query = $this->list_query();

        $posted = $this->request->getPost('ord');
        $orders = [];
        $valid  = is_array($posted) && $posted && count($posted) <= self::PAGESIZE;

        if ($valid) {
            foreach ($posted as $id => $order) {
                $order = is_string($order) ? trim($order) : '';
                if (! preg_match('/^[1-9][0-9]*$/D', (string) $id) || ! preg_match('/^[0-9]{1,4}$/D', $order)) {
                    $valid = false;
                    break;
                }
                $orders[(int) $id] = (int) $order;
            }
        }
        if (! $valid) {
            $this->session->setFlashdata('sitepanel_error', 'Enter a display order from 0 to 9999 for each item.');
            $this->back($query);
        }

        $ids = array_keys($orders);
        if (! $this->selected_rows_match($this->news_model->get_news_by_ids($ids), $ids)) {
            $this->back($query);
        }

        if ($this->news_model->update_sort_orders($orders)) {
            $this->session->setFlashdata('sitepanel_success', 'Display order updated.');
        } else {
            $this->session->setFlashdata('sitepanel_error', 'Display order could not be updated. Please try again.');
        }
        $this->back($query);
    }

    /**
     * @param array|false|null $meta Current wl_meta_tags row of the item's page (edit)
     *
     * @return list<string> Errors (empty = saved)
     */
    protected function save(?array $row, $meta = null): array
    {
        if ($meta === false) {
            return ['The item\'s SEO metadata could not be loaded. Please try again.'];
        }
        $list_url = $this->list_page_url();
        if ($list_url === '') {
            return ['The News & Events page is not registered in Meta Tags (news/index), so the item\'s address cannot be saved.'];
        }

        $v = new FormValidation($this->request, $this);
        $v->set_rules('news_title', 'Title', 'trim|required|max_length[200]');
        $v->set_rules(
            'friendly_url',
            'URL slug',
            'trim|max_length[150]|regex_match[/^[a-z0-9]+(?:-[a-z0-9]+)*$/]',
            ['regex_match' => 'The {field} may contain only lower-case letters, numbers and single hyphens, e.g. customcart-summer-sale-launch.'],
        );
        $v->set_rules('news_category', 'Type', 'trim|required|in_list[' . implode(',', NewsPage::CATEGORIES) . ']', ['in_list' => 'Choose News or Event as the {field}.']);
        $v->set_rules(
            'publish_date',
            'Publication date',
            ['trim', 'required', ['valid_day', static fn ($value) => self::valid_date((string) $value)]],
            ['valid_day' => 'Enter the {field} as a date.'],
        );
        $v->set_rules('short_description', 'Short description', 'trim|max_length[500]');
        $v->set_rules('description', 'Content', 'max_length[200000]');
        $v->set_rules('sort_order', 'Display order', 'trim|regex_match[/^[0-9]{1,4}$/]', ['regex_match' => 'The {field} must be a number from 0 to 9999.']);
        $v->set_rules('status', 'Status', 'required|in_list[1,0]');
        $v->set_rules('meta_title', 'Meta title', 'trim|max_length[450]');
        $v->set_rules('meta_keyword', 'Meta keywords', 'trim|max_length[450]');
        $v->set_rules('meta_description', 'Meta description', 'trim|max_length[650]');

        if (! $v->run()) {
            return array_values($v->error_array());
        }

        $post = fn ($field) => trim((string) $this->request->getPost($field));
        $id   = $row ? (int) $row['news_id'] : 0;

        $slug = $post('friendly_url') !== '' ? $post('friendly_url') : sp_slug($post('news_title'), 150);
        if ($slug === '') {
            return ['Enter a URL slug (the title has no letters or numbers to build one from).'];
        }
        $taken = $this->news_model->slug_taken($slug, $id);
        if ($taken === null) {
            return ['The URL slug could not be checked. Please try again.'];
        }
        if ($taken) {
            return ['Another news item already uses the URL slug "' . $slug . '".'];
        }
        $page_url = $list_url . '/' . $slug;
        $at       = $this->meta_model->get_route($page_url);
        if ($at === false) {
            return ['The page address could not be checked. Please try again.'];
        }
        if ($at && ! ($at['entity_type'] === NewsPage::META_TYPE && (int) $at['entity_id'] === $id)) {
            return ['The address ' . $page_url . ' is already used by another page. Choose another URL slug.'];
        }

        $new_file = null;
        if ($this->image_upload->has_file('news_image')) {
            $upload = $this->image_upload->save('news_image', NewsPage::IMAGE_FOLDER, 'Image');
            if (isset($upload['error'])) {
                return [$upload['error']];
            }
            $new_file = $upload['file'];
        }
        $remove = $row && $new_file === null && $this->request->getPost('remove_image') === '1';

        $content = (new HtmlSanitizer())->clean((string) $this->request->getPost('description'));
        $now     = date('Y-m-d H:i:s');
        $data    = [
            'news_title'        => $post('news_title'),
            'friendly_url'      => $slug,
            'news_category'     => $this->nullable('news_category'),
            'publish_date'      => $post('publish_date'),
            'short_description' => $this->nullable('short_description'),
            'description'       => trim(strip_tags($content, '<img>')) !== '' ? $content : null,
            'sort_order'        => (int) $post('sort_order'),
            'status'            => $post('status'),
            'news_date_updated' => $now,
        ];
        if ($new_file !== null || $remove) {
            $data['news_image'] = $new_file;
        }

        if ($row) {
            $saved = $this->news_model->safe_update(NewsModel::TABLE, $data, [NewsModel::PRIMARY_KEY => $id]);
        } else {
            $id    = $this->news_model->safe_insert(NewsModel::TABLE, $data + ['news_date_added' => $now]);
            $saved = $id !== false;
        }

        if (! $saved) {
            $new_file !== null && $this->image_upload->delete(NewsPage::IMAGE_FOLDER, $new_file);

            return ['News item could not be saved. Please try again.'];
        }
        if ($row && ($new_file !== null || $remove)) {
            $this->image_upload->delete(NewsPage::IMAGE_FOLDER, $row['news_image']);
        }

        // The item's wl_meta_tags row is its address on the website, so it always
        // exists: empty SEO fields get the defaults.
        $error = $this->meta_model->save_entity_page(NewsPage::META_TYPE, (int) $id, $page_url, [
            'meta_title'       => $post('meta_title'),
            'meta_keyword'     => $post('meta_keyword'),
            'meta_description' => $post('meta_description'),
        ], [
            'meta_title'       => $post('news_title'),
            'meta_keyword'     => $post('news_category') !== '' ? $post('news_category') : $post('news_title'),
            'meta_description' => mb_substr($post('short_description') !== '' ? $post('short_description') : $post('news_title'), 0, 160),
        ]);
        if ($error !== null) {
            $this->session->setFlashdata('sitepanel_error', 'The item was saved, but its page address could not be: ' . $error . ' Please edit the item again.');
        }

        return [];
    }

    /**
     * Address of the website's News & Events page (its wl_meta_tags row; '' = not registered).
     */
    protected function list_page_url(): string
    {
        $route = $this->meta_model->get_route_of(NewsPage::INDEX_TYPE);

        return is_array($route) ? (string) $route['page_url'] : '';
    }

    public static function valid_date(string $value): bool
    {
        $date = DateTime::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }

    /**
     * List filters to return to after an action (posted with it).
     */
    protected function list_query(): array
    {
        $keyword = mb_substr(trim((string) $this->request->getPost('keyword')), 0, 80);
        $status  = $this->status_param((string) $this->request->getPost('status_filter'));

        return array_filter(['keyword' => $keyword, 'status' => $status], static fn ($v) => $v !== '');
    }

    protected function status_param(string $value): string
    {
        return in_array($value, ['1', '0'], true) ? $value : '';
    }
}
