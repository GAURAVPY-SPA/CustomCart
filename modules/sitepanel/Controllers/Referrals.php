<?php

namespace Modules\Sitepanel\Controllers;

use App\Controllers\AdminController;
use Modules\Sitepanel\Models\ReferralModel;

/**
 * Sitepanel > Enquiries > Refer a Friend: the messages visitors sent from the
 * website's Refer a Friend popup (wl_referrals). Read-only log: no rewards are
 * attached to a referral (none are defined in the quotation).
 *
 *   index          referrals/view_referral_list (keyword search, bulk delete)
 *   view/<id>      referrals/view_referral_detail
 *   delete/<id>    POST, Delete privilege
 *   bulk           POST bulk_action=delete, ids[]
 */
class Referrals extends AdminController
{
    protected string $base           = 'sitepanel/referrals';
    protected string $item           = 'Referral';
    protected string $menu_highlight = 'referrals';

    protected ReferralModel $referral_model;

    protected function init(): void
    {
        $this->referral_model = new ReferralModel();
    }

    public function index(): string
    {
        $keyword = $this->keyword_param();
        $page    = $this->current_page();

        $data['heading_title'] = 'Refer a Friend';
        $data['breadcrumbs']   = ['Refer a Friend' => null];
        $data['res']           = $this->referral_model->get_referrals(($page - 1) * self::PAGESIZE, self::PAGESIZE, $keyword);
        $data['total']         = (int) $this->referral_model->total_rec_found;
        $data['page_links']    = admin_pagination($this->base, $data['total'], self::PAGESIZE);
        $data['keyword']       = $keyword;
        $data['page']          = $page;

        return $this->render('referrals/view_referral_list', $data);
    }

    /**
     * @param int|string $id referral_id
     */
    public function view($id = 0): string
    {
        $row = $this->record_or_back($this->referral_model->get_referral((int) $id));

        $data['heading_title'] = 'Referral #' . (int) $row['referral_id'];
        $data['breadcrumbs']   = ['Refer a Friend' => site_url($this->base), 'Referral #' . (int) $row['referral_id'] => null];
        $data['row']           = $row;

        return $this->render('referrals/view_referral_detail', $data);
    }

    /**
     * @param int|string $id referral_id
     */
    public function delete($id = 0): never
    {
        $this->delete_record(ReferralModel::TABLE, ReferralModel::PRIMARY_KEY, $id);
    }

    public function bulk(): never
    {
        $query = ['keyword' => $this->keyword_param((string) $this->request->getPost('keyword'))];
        // Referrals have no status: delete is the only bulk action.
        if ($this->request->getPost('bulk_action') !== 'delete') {
            $this->session->setFlashdata('sitepanel_error', 'Choose a valid bulk action.');
            $this->back($query);
        }

        $this->process_bulk_action($this->referral_model, 'get_referrals_by_ids', $this->item, $query);
    }
}
