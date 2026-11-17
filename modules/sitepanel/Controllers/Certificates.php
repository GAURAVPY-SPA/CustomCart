<?php

namespace Modules\Sitepanel\Controllers;

use App\Controllers\AdminController;
use App\Libraries\DocumentUpload;
use App\Libraries\FormValidation;
use App\Libraries\ImageUpload;
use Modules\Certificates\Models\CertificateModel;
use Modules\Sitepanel\Libraries\AdminAccess;

/**
 * Sitepanel > Other Management > Manage Certificates & Brochures
 * (wl_certificates): the website's /certificates and /brochure pages.
 *
 * List with title search, type and status filters and display order; add /
 * edit (Type and Title required; a certificate needs its image, a brochure
 * its PDF; the other file is optional: a certificate's PDF is linked from
 * its title); activate, deactivate and delete, one row or the selected rows
 * (with their files). A new entry goes after the last one.
 *
 *   index          certificates/view_certificate_list
 *   add, edit/<id> certificates/view_certificate_form
 *   status/<id>    POST, AdminController::update_status()
 *   delete/<id>    POST, also removes the image and the PDF
 *   bulk           POST, activate / deactivate / delete the selected rows
 *   sort           POST ord[<certificate_id>] = display order, rows of the current page
 */
class Certificates extends AdminController
{
    public const BEST_SIZE = '900 × 1200 pixels (portrait, as the certificate cards)';

    protected string $base           = 'sitepanel/certificates';
    protected string $item           = 'Entry';
    protected string $menu_highlight = 'certificates';

    protected CertificateModel $cert_model;
    protected ImageUpload $image_upload;
    protected DocumentUpload $document_upload;

    protected function init(): void
    {
        $this->cert_model      = new CertificateModel();
        $this->image_upload    = new ImageUpload();
        $this->document_upload = new DocumentUpload();
    }

    public function index(): string
    {
        $keyword = $this->keyword_param();
        $status  = $this->status_param((string) $this->request->getGet('status'));
        $type    = $this->type_param((string) $this->request->getGet('type'));
        $page    = $this->current_page();
        $res     = $this->cert_model->get_items(($page - 1) * self::PAGESIZE, self::PAGESIZE, $keyword, $status, $type);

        $data['heading_title'] = 'Manage Certificates & Brochures';
        $data['breadcrumbs']   = ['Certificates & Brochures' => null];
        $data['res']           = $res;
        $data['total']         = $this->cert_model->total_rec_found;
        $data['page']          = $page;
        $data['page_links']    = admin_pagination($this->base, $data['total'], self::PAGESIZE);
        $data['keyword']       = $keyword;
        $data['status']        = $status;
        $data['type']          = $type;

        return $this->render('certificates/view_certificate_list', $data);
    }

    public function add(): string
    {
        $this->require_privilege(AdminAccess::PRIV_ADD);
        $data['errors'] = [];

        if ($this->request->getMethod() === 'POST') {
            $data['errors'] = $this->save(null);
            if (empty($data['errors'])) {
                $this->session->setFlashdata('sitepanel_success', 'Entry added.');
                redirect_now($this->base);
            }
        }

        $type = $this->type_param((string) $this->request->getGet('type'));

        $data['heading_title'] = 'Add Certificate / Brochure';
        $data['breadcrumbs']   = ['Certificates & Brochures' => site_url($this->base), 'Add' => null];
        $data['res']           = ['cert_type' => $type !== '' ? $type : CertificateModel::TYPE_CERTIFICATE, 'title' => '', 'description' => '', 'cert_image' => null, 'cert_file' => null];
        $data['image']         = '';
        $data['file']          = '';

        return $this->render('certificates/view_certificate_form', $data);
    }

    /**
     * @param int|string $id
     */
    public function edit($id = 0): string
    {
        $res = $this->record_or_back($this->cert_model->get_item($id));
        $this->require_privilege(AdminAccess::PRIV_EDIT);
        $data['errors'] = [];

        if ($this->request->getMethod() === 'POST') {
            $data['errors'] = $this->save($res);
            if (empty($data['errors'])) {
                $this->session->setFlashdata('sitepanel_success', 'Entry updated.');
                redirect_now($this->base);
            }
        }

        $data['heading_title'] = 'Edit Certificate / Brochure';
        $data['breadcrumbs']   = ['Certificates & Brochures' => site_url($this->base), 'Edit' => null];
        $data['res']           = $res;
        $data['image']         = $this->image_upload->path(CertificateModel::IMAGE_FOLDER, $res['cert_image']);
        $data['file']          = $this->document_upload->path(CertificateModel::FILE_FOLDER, $res['cert_file']);

        return $this->render('certificates/view_certificate_form', $data);
    }

    /**
     * @param int|string $id
     */
    public function status($id = 0): void
    {
        $this->update_status(CertificateModel::TABLE, CertificateModel::PRIMARY_KEY, $id);
    }

    /**
     * POST: delete one entry with its image and PDF.
     *
     * @param int|string $id
     */
    public function delete($id = 0): void
    {
        $this->require_post();
        $this->require_privilege(AdminAccess::PRIV_DELETE);
        $row = $this->record_or_back($this->cert_model->get_item($id));

        if ($this->cert_model->safe_delete(CertificateModel::TABLE, [CertificateModel::PRIMARY_KEY => (int) $row['certificate_id']])) {
            CertificateModel::delete_files($row);
            $this->session->setFlashdata('sitepanel_success', 'Entry deleted.');
        } else {
            $this->session->setFlashdata('sitepanel_error', 'Entry could not be deleted. Please try again.');
        }
        $this->back();
    }

    /**
     * Activate / deactivate / delete; CertificateModel::bulk_delete() also removes the files.
     */
    public function bulk(): void
    {
        $this->process_bulk_action($this->cert_model, 'get_items_by_ids', 'Entry', $this->list_query());
    }

    /**
     * POST ord[<certificate_id>] => display order (0-9999) for the rows of one list page.
     */
    public function sort(): void
    {
        $this->save_display_order($this->cert_model, 'get_items_by_ids', 'entry');
    }

    /**
     * @return list<string> Errors (empty = saved)
     */
    protected function save(?array $row): array
    {
        $v = new FormValidation($this->request, $this);
        $v->set_rules('cert_type', 'Type', 'required|in_list[' . implode(',', array_keys(CertificateModel::TYPES)) . ']');
        $v->set_rules('title', 'Title', 'trim|required|max_length[150]');
        $v->set_rules('description', 'Description', 'trim|max_length[255]');

        $errors = $v->run() ? [] : array_values($v->error_array());

        $type      = (string) $this->request->getPost('cert_type');
        $has_image = $this->image_upload->has_file('cert_image');
        $has_file  = $this->document_upload->has_file('cert_file');
        $old_image = $row ? (string) $row['cert_image'] : '';
        $old_file  = $row ? (string) $row['cert_file'] : '';
        if ($type === CertificateModel::TYPE_CERTIFICATE && ! $has_image && $old_image === '') {
            $errors[] = 'Choose the certificate image to upload.';
        }
        if ($type === CertificateModel::TYPE_BROCHURE && ! $has_file && $old_file === '') {
            $errors[] = 'Choose the brochure PDF to upload.';
        }
        if ($errors) {
            return $errors;
        }

        $new_image = null;
        if ($has_image) {
            $upload = $this->image_upload->save('cert_image', CertificateModel::IMAGE_FOLDER, 'Image');
            if (isset($upload['error'])) {
                return [$upload['error']];
            }
            $new_image = $upload['file'];
        }
        $new_file = null;
        if ($has_file) {
            $upload = $this->document_upload->save_document($this->request->getFile('cert_file'), CertificateModel::FILE_FOLDER, 'PDF');
            if (isset($upload['error'])) {
                $new_image !== null && $this->image_upload->delete(CertificateModel::IMAGE_FOLDER, $new_image);

                return [$upload['error']];
            }
            $new_file = $upload['file'];
        }

        $now  = date('Y-m-d H:i:s');
        $data = [
            'cert_type'    => $type,
            'title'        => trim((string) $this->request->getPost('title')),
            'description'  => $this->nullable('description'),
            'date_updated' => $now,
        ];
        $new_image !== null && $data['cert_image'] = $new_image;
        $new_file !== null && $data['cert_file'] = $new_file;

        if ($row) {
            $saved = $this->cert_model->safe_update(CertificateModel::TABLE, $data, [CertificateModel::PRIMARY_KEY => (int) $row['certificate_id']]);
        } else {
            $order = $this->cert_model->next_sort_order();
            $saved = $order !== false && (bool) $this->cert_model->safe_insert(CertificateModel::TABLE, $data + [
                'sort_order' => $order,
                'status'     => '1',
                'date_added' => $now,
            ]);
        }

        if (! $saved) {
            $new_image !== null && $this->image_upload->delete(CertificateModel::IMAGE_FOLDER, $new_image);
            $new_file !== null && $this->document_upload->delete(CertificateModel::FILE_FOLDER, $new_file);

            return ['Entry could not be saved. Please try again.'];
        }
        if ($row) {
            $new_image !== null && $this->image_upload->delete(CertificateModel::IMAGE_FOLDER, $old_image);
            $new_file !== null && $this->document_upload->delete(CertificateModel::FILE_FOLDER, $old_file);
        }

        return [];
    }

    /**
     * List filters to return to after an action (posted with it).
     */
    protected function list_query(): array
    {
        $type = $this->type_param((string) $this->request->getPost('type_filter'));

        return parent::list_query() + ($type !== '' ? ['type' => $type] : []);
    }

    protected function type_param(string $value): string
    {
        return isset(CertificateModel::TYPES[$value]) ? $value : '';
    }
}
