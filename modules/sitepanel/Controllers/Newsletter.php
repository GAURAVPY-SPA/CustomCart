<?php

namespace Modules\Sitepanel\Controllers;

use App\Controllers\AdminController;
use App\Libraries\FormValidation;
use App\Libraries\HtmlSanitizer;
use Modules\Sitepanel\Libraries\AdminAccess;
use Modules\Sitepanel\Libraries\NewsletterMailer;
use Modules\Sitepanel\Models\NewsletterModel;

/**
 * Sitepanel > Other Management > Newsletter Management (wl_newsletters).
 *
 * As in the reference module: subscriber list, add and edit (name + email,
 * one subscription per email address), activate / deactivate and delete.
 * The list can be searched by name or email and filtered by status.
 * Selected subscribers can receive a composed HTML email after a preview
 * confirmation. Sending uses the shared Mailer (Config\Mail).
 *
 *   index          newsletter/view_newsletter_list
 *   add            newsletter/newsletter_add
 *   edit/<id>      newsletter/newsletter_edit
 *   status/<id>    POST, AdminController::update_status()
 *   delete/<id>    POST, AdminController::delete_record()
 *   bulk           POST, status/delete actions or selected-recipient composer
 *   preview_email  POST, sanitized message preview
 *   compose_email  POST, return from preview to edit the message
 *   send_email     POST, one-use confirmation flow and per-recipient delivery
 */
class Newsletter extends AdminController
{
    protected string $base           = 'sitepanel/newsletter';
    protected string $item           = 'Subscriber';
    protected string $menu_highlight = 'newsletter';
    protected NewsletterModel $newsletter_model;

    protected function init(): void
    {
        $this->newsletter_model = new NewsletterModel();
    }

    public function index(): string
    {
        $keyword = mb_substr(trim((string) $this->request->getGet('keyword')), 0, 80);
        $status  = (string) $this->request->getGet('status');
        $status  = in_array($status, ['1', '0'], true) ? $status : '';

        $page = $this->current_page();
        $res  = $this->newsletter_model->get_newsletter(self::PAGESIZE, ($page - 1) * self::PAGESIZE, $keyword, $status);

        $data['heading_title'] = 'Newsletter Management';
        $data['breadcrumbs']   = ['Newsletter' => null];
        $data['res']           = $res;
        $data['total']         = $this->newsletter_model->total_rec_found;
        $data['page']          = $page;
        $data['page_links']    = admin_pagination($this->base, $data['total'], self::PAGESIZE);
        $data['keyword']       = $keyword;
        $data['status']        = $status;

        return $this->render('newsletter/view_newsletter_list', $data);
    }

    public function add(): string
    {
        $this->require_privilege(AdminAccess::PRIV_ADD);
        $data['errors'] = [];

        if ($this->request->getMethod() === 'POST') {
            $data['errors'] = $this->save(null);
            if (empty($data['errors'])) {
                $this->session->setFlashdata('sitepanel_success', 'Subscriber added.');
                redirect_now($this->base);
            }
        }

        $data['heading_title'] = 'Add Subscriber';
        $data['breadcrumbs']   = ['Newsletter' => site_url($this->base), 'Add Subscriber' => null];

        return $this->render('newsletter/newsletter_add', $data);
    }

    /**
     * @param int|string $id
     */
    public function edit($id = 0): string
    {
        $res = $this->record_or_back($this->newsletter_model->get_subscriber_by_id($id));
        $this->require_privilege(AdminAccess::PRIV_EDIT);
        $data['errors'] = [];

        if ($this->request->getMethod() === 'POST') {
            $data['errors'] = $this->save($res);
            if (empty($data['errors'])) {
                $this->session->setFlashdata('sitepanel_success', 'Subscriber updated.');
                redirect_now($this->base);
            }
        }

        $data['heading_title'] = 'Edit Subscriber';
        $data['breadcrumbs']   = ['Newsletter' => site_url($this->base), 'Edit Subscriber' => null];
        $data['res']           = $res;

        return $this->render('newsletter/newsletter_edit', $data);
    }

    /**
     * @param int|string $id
     */
    public function status($id = 0): void
    {
        $this->update_status('wl_newsletters', 'subscriber_id', $id);
    }

    /**
     * @param int|string $id
     */
    public function delete($id = 0): void
    {
        $this->delete_record('wl_newsletters', 'subscriber_id', $id);
    }

    public function bulk(): string
    {
        $keyword = mb_substr(trim((string) $this->request->getPost('keyword')), 0, 80);
        $status  = (string) $this->request->getPost('status');
        $status  = in_array($status, ['1', '0'], true) ? $status : '';

        if ($this->request->getPost('bulk_action') === 'send_email') {
            return $this->start_email_compose($keyword, $status);
        }

        $this->process_bulk_action($this->newsletter_model, 'get_subscribers_by_ids', 'Subscriber', ['keyword' => $keyword, 'status' => $status]);
    }

    public function preview_email(): string
    {
        $this->require_post();
        $this->require_privilege(AdminAccess::PRIV_EDIT);
        $flow = $this->valid_email_flow();
        $rows = $this->email_flow_rows($flow);

        $errors = $this->validate_email_content();
        if ($errors) {
            return $this->render_email_compose($flow, $rows, $errors);
        }

        $message = (new HtmlSanitizer())->clean((string) $this->request->getPost('message'));
        if (trim(strip_tags($message, '<img><hr>')) === '') {
            return $this->render_email_compose($flow, $rows, ['The Message field has no supported content.']);
        }

        return $this->render('newsletter/newsletter_email_preview', [
            'heading_title' => 'Preview Newsletter Email',
            'breadcrumbs'   => ['Newsletter' => site_url($this->base), 'Preview Email' => null],
            'flow'          => $flow,
            'rows'          => $rows,
            'subject'       => trim((string) $this->request->getPost('subject')),
            'message'       => $message,
        ]);
    }

    public function compose_email(): string
    {
        $this->require_post();
        $this->require_privilege(AdminAccess::PRIV_EDIT);
        $flow = $this->valid_email_flow();
        $rows = $this->email_flow_rows($flow);

        // Runs the rules so set_value() shows the trimmed subject, as before.
        $this->validate_email_content();

        return $this->render_email_compose($flow, $rows, []);
    }

    public function send_email(): string
    {
        $this->require_post();
        $this->require_privilege(AdminAccess::PRIV_EDIT);
        $flow = $this->valid_email_flow();
        $rows = $this->email_flow_rows($flow);

        $errors = $this->validate_email_content();
        if ($errors) {
            return $this->render_email_compose($flow, $rows, $errors);
        }

        $message = (new HtmlSanitizer())->clean((string) $this->request->getPost('message'));
        if (trim(strip_tags($message, '<img><hr>')) === '') {
            return $this->render_email_compose($flow, $rows, ['The Message field has no supported content.']);
        }

        $subject = trim((string) $this->request->getPost('subject'));
        if (preg_match('/[\r\n]/', $subject)) {
            return $this->render_email_compose($flow, $rows, ['The Subject field cannot contain line breaks.']);
        }

        $from_email = trim((string) $this->admin['admin_email']);
        if (filter_var($from_email, FILTER_VALIDATE_EMAIL) === false) {
            return $this->render_email_compose($flow, $rows, ['The administrator email address is invalid; configure a valid sender before sending.']);
        }

        $this->session->remove('newsletter_mail_flow');
        $results = (new NewsletterMailer())->send_selected(
            $rows,
            $subject,
            $message,
            $from_email,
            $this->site_settings->get('site_name'),
        );

        $summary = $results['sent'] . ' sent from ' . $results['attempted'] . ' delivery attempt(s); '
            . $results['failed'] . ' delivery failure(s), ' . $results['invalid'] . ' invalid address(es) skipped, '
            . $results['duplicates'] . ' duplicate address(es) skipped.';
        if ($results['sent'] > 0) {
            $this->session->setFlashdata('sitepanel_success', $summary);
        } else {
            $this->session->setFlashdata('sitepanel_error', 'No newsletter emails were sent. ' . $summary);
        }

        redirect_now($this->email_list_url($flow));
    }

    protected function start_email_compose(string $keyword, string $status): string
    {
        $this->require_post();
        $this->require_privilege(AdminAccess::PRIV_EDIT);

        $ids = $this->selected_ids();
        if ($ids === false) {
            $this->session->setFlashdata('sitepanel_error', 'Select one or more valid subscribers on this page.');
            $this->back(['keyword' => $keyword, 'status' => $status]);
        }

        $rows = $this->newsletter_model->get_subscribers_by_ids($ids);
        if (! $this->selected_rows_match($rows, $ids)) {
            $this->back(['keyword' => $keyword, 'status' => $status]);
        }

        $valid_addresses = 0;

        foreach ($rows as $row) {
            if (filter_var(strtolower(trim((string) $row['subscriber_email'])), FILTER_VALIDATE_EMAIL) !== false) {
                $valid_addresses++;
            }
        }
        if ($valid_addresses === 0) {
            $this->session->setFlashdata('sitepanel_error', 'None of the selected subscribers has a valid email address.');
            $this->back(['keyword' => $keyword, 'status' => $status]);
        }

        $flow = [
            'token'   => bin2hex(random_bytes(32)),
            'ids'     => $ids,
            'page'    => max(1, (int) $this->request->getPost('page')),
            'keyword' => $keyword,
            'status'  => $status,
            'issued'  => time(),
        ];
        $this->session->set('newsletter_mail_flow', $flow);

        return $this->render_email_compose($flow, $rows, []);
    }

    /**
     * The session flow matching the posted token; otherwise back to the list.
     */
    protected function valid_email_flow(): array
    {
        $flow  = $this->session->get('newsletter_mail_flow');
        $token = $this->request->getPost('token');

        if (! is_array($flow) || ! is_string($token) || ! isset($flow['token'], $flow['ids'], $flow['issued'])
            || ! is_string($flow['token']) || ! hash_equals($flow['token'], $token) || time() - (int) $flow['issued'] > 1800) {
            $this->session->remove('newsletter_mail_flow');
            $this->session->setFlashdata('sitepanel_error', 'The newsletter email session expired. Select subscribers and try again.');
            redirect_now($this->base);
        }

        return $flow;
    }

    /**
     * The flow's subscribers reloaded from the database; otherwise back to the list.
     */
    protected function email_flow_rows(array $flow): array
    {
        $rows = $this->newsletter_model->get_subscribers_by_ids($flow['ids']);
        if (! $this->selected_rows_match($rows, $flow['ids'])) {
            $this->session->remove('newsletter_mail_flow');
            redirect_now($this->email_list_url($flow));
        }

        return $rows;
    }

    /**
     * @return list<string>
     */
    protected function validate_email_content(): array
    {
        $v = new FormValidation($this->request, $this);
        $v->set_rules('subject', 'Subject', 'trim|required|max_length[200]');
        $v->set_rules('message', 'Message', 'required|max_length[20000]');

        return $v->run() ? [] : array_values($v->error_array());
    }

    protected function render_email_compose(array $flow, array $rows, array $errors): string
    {
        $valid = 0;

        foreach ($rows as $row) {
            if (filter_var(strtolower(trim((string) $row['subscriber_email'])), FILTER_VALIDATE_EMAIL) !== false) {
                $valid++;
            }
        }

        return $this->render('newsletter/newsletter_email_compose', [
            'heading_title' => 'Compose Newsletter Email',
            'breadcrumbs'   => ['Newsletter' => site_url($this->base), 'Compose Email' => null],
            'flow'          => $flow,
            'selected'      => count($rows),
            'valid'         => $valid,
            'invalid'       => count($rows) - $valid,
            'errors'        => $errors,
        ]);
    }

    protected function email_list_url(array $flow): string
    {
        $query = [];
        if ($flow['keyword'] !== '') {
            $query['keyword'] = $flow['keyword'];
        }
        if ($flow['status'] !== '') {
            $query['status'] = $flow['status'];
        }
        if ((int) $flow['page'] > 1) {
            $query['page'] = (int) $flow['page'];
        }

        return $this->base . ($query ? '?' . http_build_query($query) : '');
    }

    /**
     * @param array|null $row NULL = add
     *
     * @return list<string> Errors (empty = saved)
     */
    protected function save(?array $row): array
    {
        $v = new FormValidation($this->request, $this);
        $v->set_rules(
            'subscriber_name',
            'Name',
            "trim|required|max_length[32]|regex_match[/^[\\p{L}][\\p{L} .'\\-]*$/u]",
            ['regex_match' => "The %s field may contain only letters, spaces and . ' -"],
        );
        $v->set_rules('subscriber_email', 'Email', 'trim|required|max_length[80]|valid_email');

        $errors = $v->run() ? [] : array_values($v->error_array());
        if ($errors) {
            return $errors;
        }

        $email = strtolower(trim((string) $this->request->getPost('subscriber_email')));
        $taken = $this->newsletter_model->checkRecords($email, $row ? $row['subscriber_id'] : null);
        if ($taken === false) {
            return ['Subscriber could not be saved. Please try again.'];
        }
        if ($taken > 0) {
            return ['This email address is already subscribed.'];
        }

        $posted_data = [
            'subscriber_name'  => trim((string) $this->request->getPost('subscriber_name')),
            'subscriber_email' => $email,
        ];

        if ($row) {
            $saved = $this->newsletter_model->safe_update('wl_newsletters', $posted_data, ['subscriber_id' => (int) $row['subscriber_id']]);
        } else {
            $posted_data += ['status' => '1', 'subscribe_date' => date('Y-m-d H:i:s')];
            $saved = (bool) $this->newsletter_model->safe_insert('wl_newsletters', $posted_data);
        }

        return $saved ? [] : ['Subscriber could not be saved. Please try again.'];
    }
}
