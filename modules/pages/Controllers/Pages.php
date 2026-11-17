<?php

namespace Modules\Pages\Controllers;

use App\Controllers\PublicController;
use App\Libraries\Captcha;
use App\Libraries\FormValidation;
use App\Libraries\HtmlSanitizer;
use App\Libraries\Mailer;
use Modules\Pages\Models\PagesModel;
use Modules\Products\Models\ProductModel;
use Modules\Sitepanel\Models\EnquiryModel;
use Modules\Sitepanel\Models\FaqModel;
use Modules\Sitepanel\Models\MetaModel;
use Modules\Sitepanel\Models\ReferralModel;
use Modules\Team\Models\TeamModel;
use Modules\Users\Libraries\CustomerAuth;
use Throwable;

/**
 * The website's static pages (Sitepanel > Manage Static Pages, wl_cms_pages),
 * each routed through its wl_meta_tags row (app/Config/ModuleRoutes.php),
 * which also holds its SEO data. A page is shown as its text (render_cms():
 * html/privacy-policy.htm, terms.htm, returns.htm, refunds.htm) or in one of
 * the designed views below (Contact Us, About Us). Pages of their own:
 * Sitemap, FAQ, and the popup pages Send Inquiry and Refer a Friend.
 *
 * SEO: the page's active wl_meta_tags row, otherwise the page title and
 * the website settings (PageMeta).
 */
class Pages extends PublicController
{
    /**
     * Form submissions (Contact Us, Send Inquiry, Refer a Friend) accepted per IP address per hour.
     */
    public const CONTACT_LIMIT_PER_HOUR = 5;

    /**
     * Verification codes (Config\Captcha::$forms).
     */
    public const CONTACT_CAPTCHA = 'contact';
    public const REFER_CAPTCHA   = 'refer';

    /**
     * Designed views of modules/pages/Views/ a static page can be shown in
     * (view => label in Manage Static Pages).
     */
    public const DESIGNED_VIEWS = [
        'contact_us' => 'Contact Us (message form)',
        'about_us'   => 'About Us (mission & vision, team)',
    ];

    /**
     * Whether a page in this layout (PagesModel::layout_of()) shows its
     * Description: a text page, Contact Us (the contact panel text) or
     * About Us (its CMS area).
     */
    public static function shows_description(string $layout): bool
    {
        return in_array($layout, [PagesModel::TEXT_LAYOUT, 'contact_us', 'about_us'], true);
    }

    /**
     * A published static page (Sitepanel > Manage Static Pages) at its
     * address: routed through its wl_meta_tags row 'pages/index/<friendly_url>'
     * (app/Config/ModuleRoutes.php), which also holds its SEO data. It is
     * shown in its layout: a designed view, or its text (cms_page).
     *
     * @param int|string $page_id wl_cms_pages.page_id (entity_id of the route row)
     */
    public function index($page_id = 0): string
    {
        $pages_model = new PagesModel();
        $page        = $pages_model->get_cms_page(['page_id' => (int) $page_id, 'status' => '1']);
        if (! is_array($page)) {
            $page === false && log_message('error', 'Pages::index could not load page ' . (int) $page_id . '.');
            $this->not_found_page();
        }

        $view   = (string) ($page['layout'] ?? '');
        $parent = (int) $page['parent_id'] > 0 ? $pages_model->get_cms_page(['page_id' => (int) $page['parent_id'], 'status' => '1']) : null;
        $parent = is_array($parent) ? $parent : null;
        $meta   = [
            'entity'      => [MetaModel::page_type($page['friendly_url']), (int) $page['page_id']],
            'title'       => $page['page_name'] . ' | ' . $this->site_settings->get('site_name'),
            'description' => PagesModel::meta_for($page['page_name'], $page['page_description'])['meta_description'],
        ];

        if (! isset(self::DESIGNED_VIEWS[$view])) {
            $view !== '' && log_message('error', 'Pages::index: page ' . (int) $page['page_id'] . ' has an unknown layout "' . $view . '"; shown as its text.');

            return $this->render_cms($page, $parent, $meta);
        }

        $data = [
            'page_meta' => $meta,
            'page'      => $page,
            'banner'    => self::banner_positions($page, $parent),
            'content'   => (new HtmlSanitizer())->clean($page['page_description']),
        ];

        if ($view === 'contact_us') {
            $data = array_merge($data, $this->contact_form($page['friendly_url']), $this->contact_details());
            $data['page_scripts'] = [Captcha::page_script()];
        } elseif ($view === 'about_us') {
            $data['team'] = $this->team_members();
        }

        return $this->render('Modules\Pages\Views\\' . $view, $data);
    }

    /**
     * Sitemap (html/sitemap.htm): a page of its own, routed through its
     * wl_meta_tags row (entity_type pages/sitemap, page_url sitemap).
     */
    public function sitemap(): string
    {
        return $this->render('Modules\Pages\Views\sitemap', [
            'page_meta' => ['title' => 'Sitemap | ' . $this->site_settings->get('site_name')],
            'banner'    => self::banner_positions(['page_name' => 'Sitemap'], null),
        ]);
    }

    /**
     * FAQ (html/faq.htm): the FAQs shown on the FAQ page (Sitepanel > Manage
     * FAQs, "Show on FAQ page"), in display order.
     */
    public function faq(): string
    {
        $faqs = (new FaqModel())->get_general_faqs();
        if ($faqs === false) {
            log_message('error', 'Pages::faq could not load the FAQs.');
        }

        return $this->render('Modules\Pages\Views\faq', [
            'page_meta'   => ['title' => 'Frequently Asked Questions | ' . $this->site_settings->get('site_name')],
            'faqs'        => is_array($faqs) ? $faqs : [],
            'unavailable' => $faqs === false,
        ]);
    }

    /**
     * Send Inquiry (html/send-enquiry.htm): the page inside the header's
     * inquiry popup (iframe). ?product=<products_id> names the product asked
     * about. POST saves a wl_enquiry row: type 3 (Manage Product Enquiry)
     * about a product, else type 1 (Manage Enquiry); the page is then shown
     * with body[data-kt-sent] so the parent page closes the popup.
     */
    public function send_inquiry(): string
    {
        $product = $this->public_product($this->request->getGet('product') ?? $this->request->getPost('product'));
        $fields  = ['name', 'company', 'email', 'phone', 'message'];
        $values  = array_fill_keys($fields, '');
        $errors  = [];
        $sent    = false;

        $customer = (new CustomerAuth())->current();
        if ($customer) {
            $values['name']  = trim($customer['first_name'] . ' ' . $customer['last_name']);
            $values['email'] = (string) $customer['user_name'];
            $values['phone'] = (string) $customer['mobile_number'];
        }

        if ($this->request->getMethod() === 'POST') {
            foreach ($fields as $field) {
                $values[$field] = trim((string) $this->request->getPost($field));
            }

            // Honeypot: shown as sent, nothing saved.
            $errors = $this->honeypot_filled() ? [] : $this->submit_inquiry($values, $product);
            $sent   = $errors === [];
            if ($sent) {
                $values = array_fill_keys($fields, '');
            }
        }

        return $this->render('Modules\Pages\Views\send_inquiry', [
            'page_meta'  => ['title' => 'Send Inquiry | ' . $this->site_settings->get('site_name')],
            'is_frame'   => true,
            'body_attrs' => $sent ? ['data-kt-sent' => 'Inquiry sent. Our team will contact you shortly.'] : [],
            'product'    => $product,
            'values'     => $values,
            'errors'     => $errors,
            'sent'       => $sent,
        ]);
    }

    /**
     * Validates a posted Send Inquiry form and saves it (send_inquiry()).
     *
     * @param array<string, string> $values The posted fields, trimmed
     *
     * @return list<string> Error messages; none when the inquiry was saved
     */
    private function submit_inquiry(array $values, ?array $product): array
    {
        $v = new FormValidation($this->request, $this);
        $v->set_data($values);
        $v->set_rules('name', 'Full name', 'required|max_length[61]|' . FormValidation::PERSON_NAME, ['regex_match' => FormValidation::PERSON_NAME_MESSAGE]);
        $v->set_rules('company', 'Company', 'max_length[60]');
        $v->set_rules('email', 'Email', 'required|max_length[80]|valid_email');
        $v->set_rules('phone', 'Phone', 'max_length[20]|regex_match[/^\+?[0-9][0-9 ()\-]{6,18}$/]', ['regex_match' => 'Please enter a valid phone number, e.g. +966 55 123 4567.']);
        $v->set_rules('message', 'Requirements', 'max_length[5000]');
        if (! $v->run()) {
            return array_values($v->error_array());
        }

        // first_name / last_name hold 30 characters each.
        [$first, $last] = split_name($values['name']);
        if (mb_strlen($first) > 30 || mb_strlen($last) > 30) {
            return ['Please shorten your name (up to 30 characters for each part).'];
        }

        $enquiry = new EnquiryModel();
        $over    = $this->over_hourly_limit($enquiry, self::CONTACT_LIMIT_PER_HOUR);
        if ($over) {
            return ['We have already received several requests from your connection. Please try again later or contact us directly.'];
        }

        // NULL: the count could not be read (database error), so nothing is saved.
        if ($over === null || ! $this->save_inquiry($enquiry, $values, $first, $last, $product)) {
            log_message('error', 'Pages::send_inquiry could not save an inquiry.');

            return ['Your inquiry could not be sent right now. Please try again in a few minutes.'];
        }

        return [];
    }

    /**
     * Saves a Send Inquiry message as a wl_enquiry row: type 3 (Manage
     * Product Enquiry) about a product, else type 1 (Manage Enquiry).
     */
    private function save_inquiry(EnquiryModel $enquiry, array $values, string $first, string $last, ?array $product): bool
    {
        return $enquiry->safe_insert('wl_enquiry', [
            'type'            => $product ? '3' : '1',
            'product_service' => $product ? mb_substr($product['product_name'] . ' (' . $product['product_code'] . ')', 0, 255) : 'Send Inquiry',
            'first_name'      => $first,
            'last_name'       => $last,
            'company_name'    => $values['company'] !== '' ? $values['company'] : null,
            'email'           => $values['email'],
            'mobile_number'   => $values['phone'] !== '' ? $values['phone'] : null,
            'message'         => $values['message'] !== '' ? $values['message'] : '(No message)',
            'ip_address'      => $this->request->getIPAddress(),
            'reply_status'    => 'N',
            'receive_date'    => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Refer a Friend (popup page, iframe): the site's share links
     * (html/refer-a-friend.htm, opened from the footer) and the "Refer to a
     * Friend" form (html/detail.htm #ktRefer: names, emails, message,
     * captcha) for the website; with ?product=<products_id> (the product
     * page's button) the form is about that product. POST saves the message
     * in wl_referrals (Sitepanel > Manage Refer a Friend) and emails the
     * friend the link (product page, else the website).
     */
    public function refer_friend(): string
    {
        $product = $this->public_product($this->request->getGet('product') ?? $this->request->getPost('product'));
        $fields  = ['your_name', 'your_email', 'friend_name', 'friend_email', 'message'];
        $values  = array_fill_keys($fields, '');
        $errors  = [];
        $sent    = false;
        $captcha = new Captcha();
        $cap_err = '';

        $customer = (new CustomerAuth())->current();
        if ($customer) {
            $values['your_name']  = trim($customer['first_name'] . ' ' . $customer['last_name']);
            $values['your_email'] = (string) $customer['user_name'];
        }

        if ($this->request->getMethod() === 'POST') {
            foreach ($fields as $field) {
                $values[$field] = trim((string) $this->request->getPost($field));
            }

            if ($this->honeypot_filled()) {
                $sent = true; // Honeypot: shown as sent (the values stay in the form), nothing saved.
            } else {
                [$errors, $cap_err] = $this->submit_referral($values, $captcha, $product, $customer);
                if ($errors === [] && $cap_err === '') {
                    $sent = true;
                    // The form keeps only the sender's name and email.
                    $values = array_merge(array_fill_keys($fields, ''), array_intersect_key($values, ['your_name' => 1, 'your_email' => 1]));
                }
            }
        }

        return $this->render('Modules\Pages\Views\refer_friend', [
            'page_meta'    => ['title' => 'Refer a Friend | ' . $this->site_settings->get('site_name')],
            'is_frame'     => true,
            'body_attrs'   => $sent ? ['data-kt-sent' => 'Thank you! Your message has been sent to your friend.'] : [],
            'product'      => $product,
            'values'       => $values,
            'errors'       => $errors,
            'sent'         => $sent,
            'page_scripts' => [Captcha::page_script()],
        ] + $captcha->view_data(self::REFER_CAPTCHA, 'referCaptcha', ['label' => 'Captcha', 'error' => $cap_err]));
    }

    /**
     * Validates a posted Refer a Friend form, saves it and emails the
     * friend (refer_friend()).
     *
     * @param array<string, string> $values The posted fields, trimmed
     *
     * @return array{0: list<string>, 1: string} [error messages, captcha error]; both empty when sent
     */
    private function submit_referral(array $values, Captcha $captcha, ?array $product, ?array $customer): array
    {
        $name_rule = 'required|max_length[100]|' . FormValidation::PERSON_NAME;
        $v         = new FormValidation($this->request, $this);
        $v->set_data($values + [$captcha->field() => (string) $this->request->getPost($captcha->field())]);
        $v->set_rules('your_name', 'Your Name', $name_rule, ['regex_match' => FormValidation::PERSON_NAME_MESSAGE]);
        $v->set_rules('your_email', 'Your Email', 'required|max_length[120]|valid_email');
        $v->set_rules('friend_name', 'Friend\'s Name', $name_rule, ['regex_match' => 'Your friend\'s name may contain only letters, spaces and . \' -']);
        $v->set_rules('friend_email', 'Friend\'s Email', 'required|max_length[120]|valid_email');
        $v->set_rules('message', 'Message', 'max_length[1000]');
        $captcha->add_rule($v, self::REFER_CAPTCHA);
        $all     = $v->run() ? [] : $v->error_array();
        $cap_err = (string) ($all[$captcha->field()] ?? '');
        unset($all[$captcha->field()]);
        if ($all !== [] || $cap_err !== '') {
            return [array_values($all), $cap_err];
        }

        if (strcasecmp($values['your_email'], $values['friend_email']) === 0) {
            return [['Please enter your friend\'s email address, not your own.'], ''];
        }

        $referrals = new ReferralModel();
        $over      = $this->over_hourly_limit($referrals, self::CONTACT_LIMIT_PER_HOUR);
        if ($over) {
            return [['We have already received several messages from your connection. Please try again later.'], ''];
        }

        // NULL: the count could not be read (database error), so nothing is saved.
        if ($over === null || ! $this->save_referral($referrals, $values, $product, $customer)) {
            log_message('error', 'Pages::refer_friend could not save a referral.');

            return [['Your message could not be sent right now. Please try again in a few minutes.'], ''];
        }

        $this->email_friend($values, $product);

        return [[], ''];
    }

    /**
     * Saves a Refer a Friend message (wl_referrals, Sitepanel > Manage Refer a Friend).
     */
    private function save_referral(ReferralModel $referrals, array $values, ?array $product, ?array $customer): bool
    {
        return $referrals->safe_insert(ReferralModel::TABLE, [
            'sender_name'   => $values['your_name'],
            'sender_email'  => strtolower($values['your_email']),
            'friend_name'   => $values['friend_name'],
            'friend_email'  => strtolower($values['friend_email']),
            'message'       => $values['message'] !== '' ? $values['message'] : null,
            'page_url'      => $product ? product_url($product['friendly_url']) : site_url(),
            'products_id'   => $product ? (int) $product['products_id'] : null,
            'customers_id'  => $customer ? (int) $customer['customers_id'] : null,
            'ip_address'    => $this->request->getIPAddress(),
            'referral_date' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Email the referred friend a link to the product (failures are logged; the referral is kept).
     */
    protected function email_friend(array $values, ?array $product): void
    {
        $from = trim((string) $this->site_settings->get('contact_email'));
        $site = (string) $this->site_settings->get('site_name');
        if ($from === '') {
            log_message('warning', 'Pages::refer_friend: no contact email in Admin Settings, the referral email was not sent.');

            return;
        }

        $url   = $product ? product_url($product['friendly_url']) : site_url();
        $title = $product ? (string) $product['product_name'] : $site;
        $body  = '<p>Dear ' . html_escape($values['friend_name']) . ',</p>'
            . '<p>' . html_escape($values['your_name']) . ' (' . html_escape($values['your_email']) . ') thought you might like ' . ($product ? 'this product from ' . html_escape($site) : html_escape($site) . ', premium everyday clothing and accessories') . ':</p>'
            . '<p><a href="' . html_escape($url) . '"><strong>' . html_escape($title) . '</strong></a></p>'
            . ($values['message'] !== '' ? '<p><em>' . nl2br(html_escape($values['message'])) . '</em></p>' : '')
            . '<p>' . html_escape($site) . '</p>';

        try {
            $mailer = new Mailer();
            if (! $mailer->send($values['friend_email'], $values['your_name'] . ' recommends ' . $title, $body, $from, $site)) {
                log_message('error', 'Pages::refer_friend: the referral email was not sent: ' . $mailer->error());
            }
        } catch (Throwable $e) {
            log_message('error', 'Pages::refer_friend: the referral email failed: ' . $e->getMessage());
        }
    }

    /**
     * A public product by its id (a query string value), or NULL.
     *
     * @param mixed $id
     */
    protected function public_product($id): ?array
    {
        helper('shop');
        if (! is_string($id) || preg_match('/^[1-9][0-9]{0,9}$/D', $id) !== 1) {
            return null;
        }
        $product = (new ProductModel())->get_public_product((int) $id);

        return is_array($product) ? $product : null;
    }

    /**
     * Team members of the About Us slider (Sitepanel > Manage Team &
     * Partners: active members, not partners), in display order.
     *
     * @return list<array{name: string, designation: string, image: string}>
     */
    protected function team_members(): array
    {
        $rows = (new TeamModel())->get_active(TeamModel::TYPE_TEAM);
        if ($rows === false) {
            log_message('error', 'Pages::team_members could not load the team.');

            return [];
        }

        $team = [];
        foreach ($rows as $row) {
            $team[] = [
                'name'        => (string) $row['name'],
                'designation' => (string) $row['designation'],
                'image'       => get_image('team', $row['photo'], 600, 600, 'C'),
            ];
        }

        return $team;
    }

    /**
     * Banner positions of a static page's hero (Sitepanel > Manage Banners,
     * see inner_banner()): the position of the page's URL (stable when the
     * page is renamed), then its page name and its parent page's name, each
     * kept only when it is a banner position.
     *
     * @param array      $page   Published wl_cms_pages row
     * @param array|null $parent Its published parent page
     *
     * @return list<string>
     */
    public static function banner_positions(array $page, ?array $parent): array
    {
        $by_url = [
            'aboutus'          => 'About Us',
            'contactus'        => 'Contact Us',
            'privacy-policy'   => 'Privacy Policy',
            'terms-conditions' => 'Terms & Conditions',
            'returns'          => 'Returns',
            'refunds'          => 'Refunds',
        ];
        $known     = config(\Modules\Sitepanel\Config\Sitepanel::class)->bannerPositions;
        $positions = [
            $by_url[(string) ($page['friendly_url'] ?? '')] ?? '',
            trim((string) $page['page_name']),
            $parent ? trim((string) $parent['page_name']) : '',
        ];

        return array_values(array_unique(array_filter($positions, static fn ($p) => $p !== '' && isset($known[$p]))));
    }

    /**
     * A page shown as its text, in the design's CMS layout (html/privacy-policy.htm).
     * The breadcrumb shows the page's parent page when it is published.
     *
     * @param array      $page   Published wl_cms_pages row
     * @param array|null $parent Its published parent page
     * @param array      $meta   page_meta
     */
    protected function render_cms(array $page, ?array $parent, array $meta): string
    {
        return $this->render('Modules\Pages\Views\cms_page', [
            'page_meta' => $meta,
            'page'      => $page,
            'parent'    => $parent,
            'content'   => (new HtmlSanitizer())->clean($page['page_description']),
            'banner'    => self::banner_positions($page, $parent),
        ]);
    }

    /**
     * 404 override (app/Config/ModuleRoutes.php): unknown URLs get the designed 404 page.
     */
    public function not_found(): string
    {
        log_message('error', '404 Page Not Found: ' . service('request')->getPath());

        return $this->render_not_found();
    }

    /**
     * Contact Us form (html/contact-us.htm: Full Name, Email, Phone, Subject,
     * Comment, Captcha). POST saves a wl_enquiry row (type 1 = Contact us;
     * Sitepanel > Manage Enquiry) and redirects to ?sent=1 (the design's
     * thank-you, html/thankyou.htm); invalid input is shown again with
     * per-field messages. The name is split into first_name / last_name,
     * the phone is mobile_number and the subject is product_service.
     *
     * @param string $url The page address (wl_cms_pages.friendly_url)
     *
     * @return array View data: contact_values, contact_errors, contact_error, contact_sent, contact_url, captcha
     */
    protected function contact_form(string $url): array
    {
        $fields  = ['name', 'email', 'phone', 'subject', 'message'];
        $captcha = new Captcha();
        $data    = [
            'contact_values' => array_fill_keys($fields, ''),
            'contact_errors' => [],
            'contact_error'  => '',
            'contact_sent'   => $this->request->getGet('sent') === '1',
            'contact_url'    => $url,
        ];

        if ($this->request->getMethod() !== 'POST') {
            return $data + $this->contact_captcha($captcha, []);
        }
        $data['contact_sent'] = false;

        foreach ($fields as $field) {
            $data['contact_values'][$field] = trim((string) $this->request->getPost($field));
        }
        $values = $data['contact_values'];

        // Honeypot: a hidden field people never fill in.
        if ($this->honeypot_filled()) {
            redirect_now($url . '?sent=1');
        }

        $v = new FormValidation($this->request, $this);
        $v->set_data($values + [$captcha->field() => (string) $this->request->getPost($captcha->field())]);
        $v->set_rules('name', 'Full name', 'required|max_length[61]|' . FormValidation::PERSON_NAME, ['regex_match' => FormValidation::PERSON_NAME_MESSAGE]);
        $v->set_rules('email', 'Email address', 'required|max_length[80]|valid_email');
        $v->set_rules(
            'phone',
            'Phone number',
            'required|max_length[20]|regex_match[/^\+?[0-9][0-9 ()\-]{6,18}$/]',
            ['regex_match' => 'Please enter a valid phone number, e.g. +966 55 123 4567.'],
        );
        $v->set_rules('subject', 'Subject', 'max_length[255]');
        $v->set_rules('message', 'Comment', 'required|min_length[10]|max_length[5000]', ['min_length' => 'Please tell us a little more in your message.']);
        $v->set_message('required', 'This field is required.');
        $captcha->add_rule($v, self::CONTACT_CAPTCHA);

        $data['contact_errors'] = $v->run() ? [] : $v->error_array();

        // first_name / last_name hold 30 characters each.
        [$first, $last] = split_name($values['name']);
        if ($values['name'] !== '' && ! isset($data['contact_errors']['name']) && (mb_strlen($first) > 30 || mb_strlen($last) > 30)) {
            $data['contact_errors']['name'] = 'Please shorten your name (up to 30 characters for each part).';
        }

        $data += $this->contact_captcha($captcha, $data['contact_errors']);
        if ($data['contact_errors']) {
            return $data;
        }

        $enquiry = new EnquiryModel();
        $over    = $this->over_hourly_limit($enquiry, self::CONTACT_LIMIT_PER_HOUR);
        if ($over) {
            $data['contact_error'] = 'We have already received several requests from your connection. Please try again later or contact us directly.';

            return $data;
        }

        // NULL: the count could not be read (database error), so nothing is saved.
        $saved = $over !== null && $enquiry->safe_insert('wl_enquiry', [
            'type'            => '1',
            'first_name'      => $first,
            'last_name'       => $last,
            'email'           => $values['email'],
            'mobile_number'   => $values['phone'] !== '' ? $values['phone'] : null,
            'product_service' => $values['subject'] !== '' ? $values['subject'] : null,
            'message'         => $values['message'],
            'ip_address'      => $this->request->getIPAddress(),
            'reply_status'    => 'N',
            'receive_date'    => date('Y-m-d H:i:s'),
        ]);

        if (! $saved) {
            log_message('error', 'Pages::contact_form could not save an enquiry.');
            $data['contact_error'] = 'Your request could not be sent right now. Please try again in a few minutes.';

            return $data;
        }

        // Post/redirect/get: a refresh cannot submit the request twice.
        redirect_now($url . '?sent=1');
    }

    /**
     * The Contact Us verification code field (app/Views/includes/captcha.php),
     * with its error from the form's errors.
     */
    protected function contact_captcha(Captcha $captcha, array $errors): array
    {
        return $captcha->view_data(self::CONTACT_CAPTCHA, 'contactCaptcha', ['label' => 'Captcha', 'error' => (string) ($errors[$captcha->field()] ?? '')]);
    }

    /**
     * Contact details, social links and map from Admin Settings (empty values
     * are left out by the view).
     */
    protected function contact_details(): array
    {
        $s       = $this->site_settings;
        $phone   = $s->get('contact_phone');
        $address = trim(str_replace('\n', "\n", $s->get('contact_address')));

        return ['contact' => [
            'address'      => $address,
            'phone'        => $phone,
            'phone_href'   => phone_digits($phone) !== '' ? 'tel:' . phone_digits($phone) : '',
            'email'        => filter_var($s->get('contact_email'), FILTER_VALIDATE_EMAIL) ? $s->get('contact_email') : '',
            'office_hours' => $s->get('office_hours'),
            'social'       => array_filter([
                'facebook'  => $s->get('facebook_link'),
                'instagram' => $s->get('instagram_link'),
                'linkedin'  => $s->get('linkedin_link'),
                'twitter-x' => $s->get('twitter_link'),
                'youtube'   => $s->get('youtube_link'),
            ], static fn ($url) => filter_var($url, FILTER_VALIDATE_URL) !== false),
            'map_src'      => map_embed_src($s->get('map_code')),
            'map_url'      => $address !== '' ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode((string) preg_replace('/\s+/', ' ', $address)) : '',
        ]];
    }
}
