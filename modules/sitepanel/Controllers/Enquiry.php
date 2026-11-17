<?php

namespace Modules\Sitepanel\Controllers;

use App\Controllers\AdminController;
use App\Libraries\FormValidation;
use App\Libraries\HtmlSanitizer;
use Modules\Sitepanel\Libraries\AdminAccess;
use Modules\Sitepanel\Libraries\EnquiryMailer;
use Modules\Sitepanel\Models\EnquiryModel;

/**
 * Sitepanel > Manage Enquiry (wl_enquiry; formerly
 * "Enquiry Management"). Contact Us form submissions arrive here as type 1.
 *
 * As in the reference module: enquiry list with search (name, email,
 * company, city, country, product/service) and a received-date range,
 * details, and delete. Send Mail (one row, or the selected rows as a bulk
 * action) emails the enquirers through the shared Mailer (app/Libraries)
 * after a preview, and marks the emailed enquiries as replied. Recipients
 * always come from the database (ids kept in a one-use session flow), never
 * from the request. The reply status can also be set by hand (Edit privilege).
 *
 *   index             enquiry/view_enquiry_list
 *   view/<id>         enquiry/view_enquiry_detail
 *   reply_status/<id> POST, Edit privilege
 *   delete/<id>       POST, AdminController::delete_record()
 *   bulk              POST, delete or Send Mail (selected rows)
 *   send_mail/<id>    POST, Send Mail to one enquiry
 *   preview_mail      POST, sanitized message preview
 *   compose_mail      POST, back from the preview to edit the message
 *   deliver_mail      POST, send, then mark the emailed enquiries as replied
 */
class Enquiry extends AdminController
{
    public const TYPES = ['1' => 'Contact us', '2' => 'Service', '3' => 'Product'];

    /**
     * Session key and lifetime (seconds) of a Send Mail compose/preview/send flow.
     */
    public const MAIL_FLOW_KEY = 'enquiry_mail_flow';
    public const MAIL_FLOW_TTL = 1800;

    protected string $base           = 'sitepanel/enquiry';
    protected string $item           = 'Enquiry';
    protected string $menu_highlight = 'enquiry';
    /**
     * Enquiry types (wl_enquiry.type) this screen lists and acts on; other
     * types are "not found" here.
     *
     * @var list<string>
     */
    protected array $scope = ['1', '2'];

    /**
     * Page heading and breadcrumb of the list.
     */
    protected string $title = 'Manage Enquiry';

    protected EnquiryModel $enquiry_model;

    protected function init(): void
    {
        $this->enquiry_model        = new EnquiryModel();
        $this->enquiry_model->types = $this->scope;
        // The enquiry views link to this screen's own URLs.
        $this->view_data['base']       = $this->base;
        $this->view_data['list_title'] = $this->title;
    }

    public function index(): string
    {
        $condition = [
            'keyword' => $this->keyword_param(),
            'from'    => $this->date_param('from'),
            'to'      => $this->date_param('to'),
            'replied' => (string) $this->request->getGet('replied'),
        ];
        if (! in_array($condition['replied'], ['Y', 'N'], true)) {
            $condition['replied'] = '';
        }

        $page = $this->current_page();
        $res  = $this->enquiry_model->get_enquiry(($page - 1) * self::PAGESIZE, self::PAGESIZE, $condition);

        $data                  = $condition;
        $data['heading_title'] = $this->title;
        $data['breadcrumbs']   = [$this->title => null];
        $data['res']           = $res;
        $data['total']         = $this->enquiry_model->total_rec_found;
        $data['page']          = $page;
        $data['page_links']    = admin_pagination($this->base, $data['total'], self::PAGESIZE);
        $data['types']         = self::TYPES;

        return $this->render('enquiry/view_enquiry_list', $data);
    }

    /**
     * @param int|string $id
     */
    public function view($id = 0): string
    {
        $res = $this->record_or_back($this->enquiry_model->get_enquiry_by_id($id));

        $data['heading_title'] = 'Enquiry Details';
        $data['breadcrumbs']   = [$this->title => site_url($this->base), 'Enquiry #' . $res['id'] => null];
        $data['res']           = $res;
        $data['types']         = self::TYPES;

        return $this->render('enquiry/view_enquiry_detail', $data);
    }

    /**
     * POST {replied: Y|N, from: list|view, page}: set the reply status.
     *
     * @param int|string $id
     */
    public function reply_status($id = 0): void
    {
        $this->require_post();
        $this->require_privilege(AdminAccess::PRIV_EDIT);
        $res     = $this->record_or_back($this->enquiry_model->get_enquiry_by_id($id));
        $replied = ($this->request->getPost('replied') === 'Y');

        if ($this->enquiry_model->update_reply_status($res['id'], $replied)) {
            $this->session->setFlashdata('sitepanel_success', $replied ? 'Enquiry marked as replied.' : 'Enquiry marked as not replied.');
        } else {
            $this->session->setFlashdata('sitepanel_error', 'Enquiry could not be updated. Please try again.');
        }

        if ($this->request->getPost('from') === 'view') {
            redirect_now($this->base . '/view/' . (int) $res['id']);
        }
        $this->back();
    }

    /**
     * @param int|string $id
     */
    public function delete($id = 0): void
    {
        $this->require_post();
        // Only an enquiry of this screen's types.
        $this->record_or_back($this->enquiry_model->get_enquiry_by_id($id));
        $this->delete_record('wl_enquiry', 'id', $id);
    }

    public function bulk(): string
    {
        $filters = $this->posted_filters();

        if ($this->request->getPost('bulk_action') === 'send_email') {
            return $this->start_mail($this->selected_ids(), $filters);
        }

        $this->process_bulk_action($this->enquiry_model, 'get_enquiries_by_ids', 'Enquiry', $filters);
    }

    /**
     * POST: Send Mail to one enquiry (row action); list filters come with the form.
     *
     * @param int|string $id
     */
    public function send_mail($id = 0): string
    {
        $id = (string) $id;

        return $this->start_mail(preg_match('/^[1-9][0-9]{0,9}$/D', $id) ? [(int) $id] : false, $this->posted_filters());
    }

    public function preview_mail(): string
    {
        [$flow, $rows] = $this->mail_flow_and_rows();

        $content = $this->valid_mail_content();
        if (isset($content['errors'])) {
            return $this->render_mail_compose($flow, $rows, $content['errors']);
        }

        $valid = $this->valid_mail_rows($rows);

        return $this->render('enquiry/view_enquiry_mail_preview', [
            'heading_title' => 'Preview Email',
            'breadcrumbs'   => [$this->title => site_url($this->mail_list_url($flow)), 'Preview Email' => null],
            'flow'          => $flow,
            'rows'          => $rows,
            'valid'         => $valid,
            'subject'       => $content['subject'],
            'message'       => $content['message'],
            // What the first valid recipient will receive (greeting + message + signature).
            'cancel_url' => site_url($this->mail_list_url($flow)),
            'sample'     => $valid ? (new EnquiryMailer())->personalise(reset($valid), $content['message'], $this->site_settings->get('site_name')) : '',
        ]);
    }

    public function compose_mail(): string
    {
        [$flow, $rows] = $this->mail_flow_and_rows();

        // set_value() in the composer refills subject/message from this POST.
        return $this->render_mail_compose($flow, $rows, []);
    }

    public function deliver_mail(): string
    {
        [$flow, $rows] = $this->mail_flow_and_rows();

        $content = $this->valid_mail_content();
        if (isset($content['errors'])) {
            return $this->render_mail_compose($flow, $rows, $content['errors']);
        }

        $from_email = trim((string) $this->admin['admin_email']);
        if (filter_var($from_email, FILTER_VALIDATE_EMAIL) === false) {
            return $this->render_mail_compose($flow, $rows, ['Your administrator email address is invalid; set a valid one before sending.']);
        }

        // One use: a refresh or a second click cannot send the same email again.
        $this->session->remove(static::MAIL_FLOW_KEY);

        $mailer  = new EnquiryMailer();
        $results = $mailer->send_enquiries($rows, $content['subject'], $content['message'], $from_email, $this->site_settings->get('site_name'));

        if ($results['sent_ids'] && ! $this->enquiry_model->mark_replied($results['sent_ids'])) {
            log_message('error', 'Enquiry::deliver_mail could not mark enquiries as replied: ' . implode(',', $results['sent_ids']));
        }

        // "Sent" = accepted by the configured mail transport for delivery (Mailer); delivery itself
        // cannot be confirmed here. Messages never name the transport; the details go to the log.
        $summary = $results['sent'] . ' of ' . $results['attempted'] . ' email(s) sent';
        $summary .= $results['failed'] ? ', ' . $results['failed'] . ' could not be sent' : '';
        $summary .= $results['invalid'] ? ', ' . $results['invalid'] . ' invalid address(es) skipped' : '';
        $summary .= $results['duplicates'] ? ', ' . $results['duplicates'] . ' duplicate address(es) emailed once' : '';
        $reasons = implode(' ', $mailer->errors());

        if ($results['failed'] || $results['sent'] === 0) {
            log_message('error', 'Enquiry::deliver_mail [' . $mailer->method_label() . ']: ' . $summary . ($reasons !== '' ? '. Reason: ' . $reasons : ''));
        }
        if ($results['sent'] > 0 && $results['failed'] === 0) {
            $this->session->setFlashdata('sitepanel_success', 'Email sent successfully.');
            $results['invalid'] && $this->session->setFlashdata('sitepanel_error', $results['invalid'] . ' invalid email address(es) were skipped.');
        } elseif ($results['sent'] > 0) {
            $this->session->setFlashdata('sitepanel_error', $results['failed'] . ' of ' . ($results['sent'] + $results['failed']) . ' email(s) could not be sent; the others were sent and marked as replied. Please try the failed ones again or check the outgoing mail settings.');
        } else {
            $this->session->setFlashdata('sitepanel_error', 'The email could not be sent. Please check the outgoing mail settings and try again.');
        }

        redirect_now($this->mail_list_url($flow));
    }

    /**
     * Start the Send Mail flow for verified enquiry ids: render the composer.
     *
     * @param false|list<int> $ids
     * @param array           $filters keyword, from, to, replied (to return to the same list)
     */
    protected function start_mail($ids, array $filters): string
    {
        $this->require_post();
        $this->require_privilege(AdminAccess::PRIV_EDIT);

        if ($ids === false) {
            $this->session->setFlashdata('sitepanel_error', 'Select one or more valid enquiries on this page.');
            $this->back(array_filter($filters, 'strlen'));
        }

        $rows = $this->enquiry_model->get_enquiries_by_ids($ids);
        if (! $this->selected_rows_match($rows, $ids)) {
            $this->back(array_filter($filters, 'strlen'));
        }

        if (! $this->valid_mail_rows($rows)) {
            $this->session->setFlashdata('sitepanel_error', count($ids) === 1
                ? 'This enquiry has no valid email address.'
                : 'None of the selected enquiries has a valid email address.');
            $this->back(array_filter($filters, 'strlen'));
        }

        $flow = $filters + [
            'token'  => bin2hex(random_bytes(32)),
            'ids'    => $ids,
            'page'   => max(1, (int) $this->request->getPost('page')),
            'issued' => time(),
        ];
        $this->session->set(static::MAIL_FLOW_KEY, $flow);

        return $this->render_mail_compose($flow, $rows, []);
    }

    /**
     * Checks shared by preview_mail, compose_mail and deliver_mail: POST, Edit
     * privilege, the session flow's token, and its enquiries reloaded from the
     * database. Redirects (and stops) when anything fails.
     *
     * @return array [flow, rows]
     */
    protected function mail_flow_and_rows(): array
    {
        $this->require_post();
        $this->require_privilege(AdminAccess::PRIV_EDIT);

        $flow  = $this->session->get(static::MAIL_FLOW_KEY);
        $token = $this->request->getPost('token');
        if (! is_array($flow) || ! is_string($token) || ! isset($flow['token'], $flow['ids'], $flow['issued'])
            || ! hash_equals((string) $flow['token'], $token) || time() - (int) $flow['issued'] > self::MAIL_FLOW_TTL) {
            $this->session->remove(static::MAIL_FLOW_KEY);
            $this->session->setFlashdata('sitepanel_error', 'The Send Mail session expired or was already used. Select the enquiries and try again.');
            redirect_now($this->base);
        }

        $rows = $this->enquiry_model->get_enquiries_by_ids($flow['ids']);
        if (! $this->selected_rows_match($rows, $flow['ids'])) {
            $this->session->remove(static::MAIL_FLOW_KEY);
            redirect_now($this->mail_list_url($flow));
        }

        return [$flow, $rows];
    }

    /**
     * @return array ['subject', 'message'] or ['errors' => list<string>]
     */
    protected function valid_mail_content(): array
    {
        $v = new FormValidation($this->request, $this);
        $v->set_rules('subject', 'Subject', 'trim|required|max_length[200]');
        $v->set_rules('message', 'Message', 'required|max_length[20000]');
        if (! $v->run()) {
            return ['errors' => array_values($v->error_array())];
        }

        $subject = trim((string) $this->request->getPost('subject'));
        if (preg_match('/[\r\n]/', $subject)) {
            return ['errors' => ['The Subject field cannot contain line breaks.']];
        }

        $message = (new HtmlSanitizer())->clean((string) $this->request->getPost('message'));
        if (trim(strip_tags($message, '<img><hr>')) === '') {
            return ['errors' => ['The Message field has no supported content.']];
        }

        return ['subject' => $subject, 'message' => $message];
    }

    /**
     * Rows with a valid email address (keys kept).
     */
    protected function valid_mail_rows(array $rows): array
    {
        return array_filter($rows, static fn ($row) => filter_var(strtolower(trim((string) $row['email'])), FILTER_VALIDATE_EMAIL) !== false);
    }

    protected function render_mail_compose(array $flow, array $rows, array $errors): string
    {
        return $this->render('enquiry/view_enquiry_mail_compose', [
            'heading_title' => 'Send Mail',
            'breadcrumbs'   => [$this->title => site_url($this->mail_list_url($flow)), 'Send Mail' => null],
            'flow'          => $flow,
            'rows'          => $rows,
            'valid'         => count($this->valid_mail_rows($rows)),
            'errors'        => $errors,
            'cancel_url'    => site_url($this->mail_list_url($flow)),
        ]);
    }

    /**
     * The list page (with its filters and page) the flow started from.
     */
    protected function mail_list_url(array $flow): string
    {
        $query = [];

        foreach (['keyword', 'from', 'to', 'replied'] as $key) {
            if (isset($flow[$key]) && $flow[$key] !== '') {
                $query[$key] = $flow[$key];
            }
        }
        if (isset($flow['page']) && (int) $flow['page'] > 1) {
            $query['page'] = (int) $flow['page'];
        }

        return $this->base . ($query ? '?' . http_build_query($query) : '');
    }

    /**
     * The list filters posted with an action (keyword, from, to, replied),
     * validated; empty values are kept.
     *
     * @return array{keyword: string, from: string, to: string, replied: string}
     */
    protected function posted_filters(): array
    {
        $replied = (string) $this->request->getPost('replied');

        return [
            'keyword' => $this->keyword_param((string) $this->request->getPost('keyword')),
            'from'    => $this->valid_date((string) $this->request->getPost('from')),
            'to'      => $this->valid_date((string) $this->request->getPost('to')),
            'replied' => in_array($replied, ['Y', 'N'], true) ? $replied : '',
        ];
    }

    /**
     * @param string $name GET parameter
     *
     * @return string Valid Y-m-d date or ''
     */
    protected function date_param(string $name): string
    {
        return $this->valid_date((string) $this->request->getGet($name));
    }

    /**
     * @return string Valid Y-m-d date or ''
     */
    protected function valid_date(string $value): string
    {
        return sp_valid_date($value) ? $value : '';
    }
}
