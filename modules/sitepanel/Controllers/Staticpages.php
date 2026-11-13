<?php

namespace Modules\Sitepanel\Controllers;

use App\Controllers\AdminController;
use App\Libraries\FormValidation;
use App\Libraries\HtmlSanitizer;
use Modules\Pages\Controllers\Pages;
use Modules\Pages\Models\PagesModel;
use Modules\Sitepanel\Libraries\AdminAccess;

/**
 * Sitepanel > Other Management > Manage Static Pages (wl_cms_pages).
 *
 * List (search by page title, status filter, paging), view and edit, as
 * before; plus add, activate / deactivate and delete, one page or the selected
 * pages. A page has a title, a page address (friendly URL: small letters,
 * digits and hyphens, unique, not used by another website page), the
 * Description, edited with CKEditor (assets/sitepanel/js/editor.js), and
 * its SEO data (meta title, description, keywords).
 *
 * As in the reference, the page's address and SEO data are its
 * wl_meta_tags row, written in the same transaction as the page
 * (PagesModel::save_page()): added with the page, updated with it (a
 * changed address moves the row), removed when the page is deleted. The
 * website routes the address through that row (entity_type
 * pages/index/<page address>) to Pages::index, which shows the page in its
 * layout (wl_cms_pages.layout: the page text, or a designed view).
 *
 * Delete sets status '2' ("Deleted", as the table defines): the page is no
 * longer listed or shown, as deleted pages were in the reference.
 *
 * The Description is HTML: it is cleaned with HtmlSanitizer when saved
 * (and again when shown), keeping the formatting the editor produces.
 *
 *   index          staticpage/staticpage_list_index_view
 *   view/<id>      staticpage/statispage_detail_view (the reference's pagedatadisplay)
 *   add            staticpage/statispage_edit_view
 *   edit/<id>      staticpage/statispage_edit_view
 *   status/<id>    POST {status: 1|0}
 *   delete/<id>    POST
 *   bulk           POST, activate / deactivate / delete the selected pages
 *   upload_image   POST, an image for the Description (AdminController::upload_image)
 */
class Staticpages extends AdminController
{
    /**
     * Page addresses that can never be used (sections of the application).
     */
    public const RESERVED_URLS = ['sitepanel', 'uploads', 'uploaded_files', 'assets', 'html', 'home', 'index.php'];

    protected string $base           = 'sitepanel/staticpages';
    protected string $item           = 'Page';
    protected string $menu_highlight = 'staticpages';
    protected array $status_words    = ['activated', 'deactivated'];
    protected PagesModel $pages_model;

    protected function init(): void
    {
        $this->pages_model = new PagesModel();
    }

    public function index(): string
    {
        $keyword = mb_substr(trim((string) $this->request->getGet('keyword')), 0, 50);
        $status  = $this->status_param((string) $this->request->getGet('status'));

        $page = $this->current_page();
        $res  = $this->pages_model->get_all_cms_page(($page - 1) * self::PAGESIZE, self::PAGESIZE, $keyword, $status);

        $data['heading_title'] = 'Manage Static Pages';
        $data['breadcrumbs']   = ['Static Pages' => null];
        $data['res']           = $res;
        $data['total']         = $this->pages_model->total_rec_found;
        $data['page']          = $page;
        $data['per_page']      = self::PAGESIZE;
        $data['page_links']    = admin_pagination($this->base, $data['total'], self::PAGESIZE);
        $data['keyword']       = $keyword;
        $data['status']        = $status;

        return $this->render('staticpage/staticpage_list_index_view', $data);
    }

    /**
     * Page information and a preview of the content.
     *
     * @param int|string $id
     */
    public function view($id = 0): string
    {
        $res = $this->record_or_back($this->pages_model->get_cms_page_by_id($id));

        $data['heading_title'] = 'View Static Page';
        $data['breadcrumbs']   = ['Static Pages' => site_url($this->base), $res['page_name'] => null];
        $data['res']           = $res;
        $data['content']       = (new HtmlSanitizer())->clean($res['page_description']);

        return $this->render('staticpage/statispage_detail_view', $data);
    }

    public function add(): string
    {
        $this->require_privilege(AdminAccess::PRIV_ADD);
        $data['errors'] = [];

        if ($this->request->getMethod() === 'POST') {
            $data['errors'] = $this->save(null);
            if (empty($data['errors'])) {
                $this->session->setFlashdata('sitepanel_success', 'Page added.');
                redirect_now($this->base);
            }
        }

        $data['heading_title'] = 'Add Static Page';
        $data['breadcrumbs']   = ['Static Pages' => site_url($this->base), 'Add Static Page' => null];
        $data['res']           = ['page_name' => '', 'friendly_url' => '', 'page_description' => ''];
        $data['seo']           = ['meta_title' => '', 'meta_description' => '', 'meta_keyword' => ''];
        $data['layout']        = PagesModel::TEXT_LAYOUT;

        return $this->render('staticpage/statispage_edit_view', $data);
    }

    /**
     * @param int|string $id
     */
    public function edit($id = 0): string
    {
        $res = $this->record_or_back($this->pages_model->get_cms_page_by_id($id));
        $this->require_privilege(AdminAccess::PRIV_EDIT);
        $data['errors'] = [];

        if ($this->request->getMethod() === 'POST') {
            $data['errors'] = $this->save($res);
            if (empty($data['errors'])) {
                $this->session->setFlashdata('sitepanel_success', 'Page updated.');
                redirect_now($this->base);
            }
        }

        $data['heading_title'] = 'Edit Static Page';
        $data['breadcrumbs']   = ['Static Pages' => site_url($this->base), 'Edit Static Page' => null];
        $data['res']           = $res;
        $data['seo']           = (array) $this->pages_model->get_page_meta($res) + ['meta_title' => '', 'meta_description' => '', 'meta_keyword' => '', 'entity_type' => null];
        $data['layout']        = PagesModel::layout_of($res['layout'] ?? null);

        return $this->render('staticpage/statispage_edit_view', $data);
    }

    /**
     * POST {status: 1|0}: activate / deactivate.
     *
     * @param int|string $id
     */
    public function status($id = 0): void
    {
        $this->require_post();
        $this->record_or_back($this->pages_model->get_cms_page_by_id($id));
        $this->update_status('wl_cms_pages', 'page_id', $id);
    }

    /**
     * @param int|string $id
     */
    public function delete($id = 0): void
    {
        $this->require_post();
        $this->require_privilege(AdminAccess::PRIV_DELETE);
        $res = $this->record_or_back($this->pages_model->get_cms_page_by_id($id));
        $error = $this->designed_pages_error([$res]);
        if ($error !== null) {
            $this->session->setFlashdata('sitepanel_error', $error);
            $this->back();
        }

        if ($this->pages_model->bulk_delete([(int) $res['page_id']])) {
            $this->session->setFlashdata('sitepanel_success', 'Page deleted.');
        } else {
            $this->session->setFlashdata('sitepanel_error', 'Page could not be deleted. Please try again.');
        }
        $this->back();
    }

    public function bulk(): void
    {
        $keyword = mb_substr(trim((string) $this->request->getPost('keyword')), 0, 50);
        $status  = $this->status_param((string) $this->request->getPost('status_filter'));
        $query   = array_filter(['keyword' => $keyword, 'status' => $status]);

        if ($this->request->getPost('bulk_action') === 'delete') {
            $this->bulk_delete_checked($this->pages_model, 'get_cms_pages_by_ids', 'Page', $query, fn (array $rows) => $this->designed_pages_error($rows));
        }
        $this->process_bulk_action($this->pages_model, 'get_cms_pages_by_ids', 'Page', $query);
    }

    /**
     * Pages shown in a designed view are part of the website's structure
     * (Contact form, Sitemap, legal pages): they are deactivated, not deleted.
     *
     * @param list<array> $rows wl_cms_pages rows
     */
    protected function designed_pages_error(array $rows): ?string
    {
        foreach ($rows as $row) {
            if (PagesModel::layout_of($row['layout'] ?? null) !== PagesModel::TEXT_LAYOUT) {
                return '"' . $row['page_name'] . '" is a built-in page of the website (its own design) and cannot be deleted. Deactivate it instead.';
            }
        }

        return null;
    }

    /**
     * Add or update a page and its SEO record (wl_meta_tags) together.
     * Empty SEO fields are filled from the page, as the reference's
     * create_meta() did: title = Page Title, description = the start of the
     * text, keywords = Page Title.
     *
     * @return list<string> Errors (empty = saved)
     */
    protected function save(?array $res): array
    {
        $page_id = $res ? (int) $res['page_id'] : 0;
        $v       = new FormValidation($this->request, $this);
        $v->set_rules('page_name', 'Page Title', 'trim|required|max_length[50]');
        $v->set_rules('friendly_url', 'Page address', ['trim', 'required', 'max_length[50]', 'regex_match[/^[a-z0-9]+(?:-[a-z0-9]+)*(?:\/[a-z0-9]+(?:-[a-z0-9]+)*)*$/]',
            ['url_free', fn ($value) => $this->url_is_free(trim((string) $value), $page_id)]],
            ['regex_match' => 'The {field} may only contain small letters (a-z), digits, single hyphens between words and / between parts, e.g. mission-and-story or our-team/jane-tan.',
                'url_free' => 'The {field} is already used by another page of the website.']);
        // New pages show their text; a built-in page keeps its own design (set by the seeder).
        $layout = $res ? PagesModel::layout_of($res['layout'] ?? null) : PagesModel::TEXT_LAYOUT;
        $v->set_rules('page_description', 'Description', (Pages::shows_description($layout) ? 'trim|required' : 'trim') . '|max_length[200000]');
        $v->set_rules('meta_title', 'Meta title', 'trim|max_length[450]');
        $v->set_rules('meta_description', 'Meta description', 'trim|max_length[650]');
        $v->set_rules('meta_keyword', 'Meta keywords', 'trim|max_length[450]');

        $errors = $v->run() ? [] : array_values($v->error_array());
        if ($errors) {
            return $errors;
        }

        $content = (new HtmlSanitizer())->clean((string) $this->request->getPost('page_description'));
        if (Pages::shows_description($layout) && trim(strip_tags($content, '<img><hr>')) === '') {
            return ['The Description field has no content left after removing unsupported HTML.'];
        }

        $name        = trim((string) $this->request->getPost('page_name'));
        $posted_data = [
            'page_name'         => $name,
            'friendly_url'      => trim((string) $this->request->getPost('friendly_url')),
            'layout'            => $layout === PagesModel::TEXT_LAYOUT ? null : $layout,
            'page_description'  => $content,
            'page_updated_date' => date('Y-m-d H:i:s'),
        ];
        if (! $res) {
            $posted_data += ['parent_id' => 0, 'page_short_description' => null, 'image' => '', 'status' => '1'];
        }

        $meta = PagesModel::meta_for($name, $content, [
            'meta_title'       => $this->request->getPost('meta_title'),
            'meta_description' => $this->request->getPost('meta_description'),
            'meta_keyword'     => $this->request->getPost('meta_keyword'),
        ]);

        return $this->pages_model->save_page($posted_data, $meta, $res) !== false
            ? []
            : ['Page could not be saved. Please try again.'];
    }

    /**
     * A page address no other page or module of the website uses
     * (wl_meta_tags, the URL registry, as in the reference's URL check), nor
     * a route of the application or a reserved address.
     */
    protected function url_is_free(string $url, int $page_id): bool
    {
        if (in_array(explode('/', $url)[0], self::RESERVED_URLS, true) || isset(service('routes')->getRoutes('GET')[$url])) {
            return false;
        }

        return $this->pages_model->url_available($url, $page_id) === true;
    }

    protected function status_param(string $value): string
    {
        return in_array($value, ['1', '0'], true) ? $value : '';
    }
}
