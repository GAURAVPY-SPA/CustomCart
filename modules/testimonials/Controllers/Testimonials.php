<?php

namespace Modules\Testimonials\Controllers;

use App\Controllers\PublicController;
use App\Libraries\Captcha;
use App\Libraries\FormValidation;
use App\Libraries\ImageUpload;
use Modules\Testimonials\Models\TestimonialModel;

/**
 * Public /testimonials page (design: html/testimonial.htm).
 *
 * GET lists the active testimonials (Sitepanel > Testimonials). POST is the
 * "Post a Testimonial" form (name, email, comment, optional photo, captcha;
 * the photo is saved by ImageUpload in uploads/testimonial/ with the limits of
 * Sitepanel > Testimonials and shown in the round avatar): a valid
 * submission is saved inactive (pending review — it is only published once
 * an administrator activates it) and the page redirects to ?submitted=1.
 * Spam controls: CSRF, the captcha, a hidden honeypot field and a per-IP limit.
 */
class Testimonials extends PublicController
{
    public const LIMIT_PER_HOUR = 3;

    /**
     * Upload folder (Config\Uploads::$folders) and largest photo in pixels, as in Sitepanel > Testimonials.
     */
    public const IMAGE_FOLDER   = 'testimonial';
    public const PHOTO_MAX_SIZE = [2000, 2000];

    /**
     * Verification code of the form (Config\Captcha::$forms).
     */
    public const CAPTCHA = 'testimonial';

    protected TestimonialModel $testimonial_model;

    protected function init(): void
    {
        $this->testimonial_model = new TestimonialModel();
    }

    public function index(): string
    {
        $form = [
            'values'        => ['name' => '', 'email' => '', 'message' => ''],
            'errors'        => [],
            'captcha_error' => '',
            'submitted'     => $this->request->getGet('submitted') === '1',
        ];
        if ($this->request->getMethod() === 'POST') {
            $form = $this->submit($form);
        }

        $rows = $this->testimonial_model->get_public_testimonials();
        if ($rows === false) {
            log_message('error', 'Testimonials::index could not load testimonials.');
        }

        return $this->render('Modules\Testimonials\Views\testimonials', [
            // SEO defaults; the page's wl_meta_tags row (testimonials/index) holds the SEO data used.
            'page_meta'    => ['title' => 'Testimonials | CustomCart', 'description' => 'Read what our customers say about CustomCart, and share your own testimonial.'],
            'testimonials' => is_array($rows) ? $rows : [],
            'form'         => $form,
            'page_scripts' => [Captcha::page_script()],
        ] + (new Captcha())->view_data(self::CAPTCHA, 'testiCaptcha', ['label' => 'Captcha', 'error' => $form['captcha_error']]));
    }

    /**
     * Validate and save a public submission (redirects when saved).
     *
     * @return array $form with values and errors
     */
    protected function submit(array $form): array
    {
        $form['submitted'] = false;

        foreach (array_keys($form['values']) as $field) {
            $form['values'][$field] = trim((string) $this->request->getPost($field));
        }

        // Honeypot: a hidden field people never fill in.
        if (trim((string) $this->request->getPost('fax_number')) !== '') {
            redirect_now('testimonials?submitted=1');
        }

        $captcha = new Captcha();
        $v       = new FormValidation($this->request, $this);
        $v->set_data($form['values'] + [$captcha->field() => (string) $this->request->getPost($captcha->field())]);
        $v->set_rules('name', 'Name', "required|max_length[30]|regex_match[/^[\\p{L}][\\p{L} .'\\-]*$/u]", ['regex_match' => 'Your name may contain only letters, spaces and . \' -']);
        $v->set_rules('email', 'Email', 'required|max_length[80]|valid_email');
        $v->set_rules('message', 'Comment', 'required|min_length[20]|max_length[2000]', ['min_length' => 'Please write at least 20 characters for your testimonial.']);
        $captcha->add_rule($v, self::CAPTCHA);

        if (! $v->run()) {
            $errors                = $v->error_array();
            $form['captcha_error'] = (string) ($errors[$captcha->field()] ?? '');
            unset($errors[$captcha->field()]);
            $form['errors'] = array_values($errors);

            return $form;
        }

        $ip     = $this->request->getIPAddress();
        $recent = $this->testimonial_model->count_recent_from_ip($ip, 60);
        if ($recent === false) {
            $form['errors'] = ['Your testimonial could not be sent right now. Please try again in a few minutes.'];

            return $form;
        }
        if ($recent >= self::LIMIT_PER_HOUR) {
            $form['errors'] = ['We have already received several testimonials from your connection. Please try again later.'];

            return $form;
        }

        // Optional photo (checked last, so it is only stored for a submission that is otherwise valid).
        $images = new ImageUpload();
        $photo  = null;
        if ($images->has_file('photo')) {
            $upload = $images->save('photo', self::IMAGE_FOLDER, 'Photo', self::PHOTO_MAX_SIZE);
            if (isset($upload['error'])) {
                $form['errors'] = [$upload['error'] . ' Choose a JPG, PNG, GIF or WEBP image of at most 2 MB.'];

                return $form;
            }
            $photo = $upload['file'];
        }

        $slug  = $this->testimonial_model->unique_slug($form['values']['name']);
        $saved = ($slug !== null) && $this->testimonial_model->safe_insert('wl_testimonial', [
            'poster_name'             => $form['values']['name'],
            'poster_role'             => null,
            'email'                   => $form['values']['email'],
            'photo'                   => $photo,
            'friendly_url'            => $slug,
            'testimonial_description' => $form['values']['message'],
            'posted_date'             => date('Y-m-d H:i:s'),
            'submitted_ip'            => $ip,
            'status'                  => '0',
        ]);

        if (! $saved) {
            $photo !== null && $images->delete(self::IMAGE_FOLDER, $photo);
            log_message('error', 'Testimonials::submit could not save a testimonial.');
            $form['errors'] = ['Your testimonial could not be sent right now. Please try again in a few minutes.'];

            return $form;
        }

        redirect_now('testimonials?submitted=1');
    }
}
