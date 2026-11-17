<?php

namespace Modules\Home\Controllers;

use App\Controllers\PublicController;
use App\Libraries\FormValidation;
use App\Libraries\ImageUpload;
use Modules\Category\Models\CategoryModel;
use Modules\Products\Models\ProductModel;
use Modules\Sitepanel\Controllers\HeaderImages;
use Modules\Sitepanel\Models\FaqModel;
use Modules\Sitepanel\Models\HeaderImagesModel;
use Modules\Sitepanel\Models\NewsletterModel;
use Modules\Team\Models\TeamModel;

/**
 * Homepage (/ and /home; design: html/index.htm). SEO: the wl_meta_tags
 * 'home' row, else the website settings (PageMeta).
 *
 * The design's sections in order, each from its Sitepanel module (a section
 * without records is left out):
 *
 *   Hero slider        Manage Header Images (line 1 = eyebrow, line 2 = title,
 *                      "Title | blue part" splits the title, line 3 = text);
 *                      none yet: the design's first slide
 *   Categories         Manage Categories: top categories flagged "Home", else all
 *   Specialization     the design's content
 *   Featured Products  products flagged Featured, else the newest
 *   New Products       products flagged New, else the newest
 *   Our Partners       Manage Team & Partners (type Partner)
 *   FAQ                Manage FAQs (shown on the FAQ page), first FAQ_COUNT
 *   Newsletter         POST email: Manage Newsletter (wl_newsletters)
 */
class Home extends PublicController
{
    public const SLIDE_COUNT    = 10;
    public const CATEGORY_COUNT = 6;
    public const PRODUCT_COUNT  = 8;
    public const PARTNER_COUNT  = 20;
    public const FAQ_COUNT      = 5;
    public const TESTIMONIAL_COUNT = 3;

    /**
     * Newsletter form result shown under it (session flashdata key).
     */
    protected const NEWSLETTER_FLASH = 'home_newsletter';

    protected $helpers = ['shop', 'text'];

    protected ImageUpload $images;
    protected ProductModel $products;

    protected function init(): void
    {
        $this->images   = new ImageUpload();
        $this->products = new ProductModel();
    }

    public function index(): string
    {
        if ($this->request->getMethod() === 'POST') {
            $this->subscribe();
        }

        return $this->render('Modules\Home\Views\home', [
            'slides'     => $this->slides(),
            'categories' => $this->categories(),
            'featured'   => product_cards($this->flagged('is_featured')),
            'new'        => product_cards($this->flagged('is_new')),
            'partners'   => $this->partners(),
            'testimonials' => $this->testimonials(),
            'faqs'       => array_slice((array) ((new FaqModel())->get_general_faqs() ?: []), 0, self::FAQ_COUNT),
            'newsletter' => session()->getFlashdata(self::NEWSLETTER_FLASH),
        ]);
    }

    /**
     * Newsletter form (POST email): add the address to Manage Newsletter,
     * then back to the form (post/redirect/get) with the result.
     */
    protected function subscribe(): never
    {
        $back  = site_url() . '#newsletter';
        $email = trim((string) $this->request->getPost('email'));

        // Honeypot: a hidden field people never fill in.
        if (trim((string) $this->request->getPost('website')) !== '') {
            redirect_now($back);
        }

        $v = new FormValidation($this->request, $this);
        $v->set_data(['email' => $email]);
        $v->set_rules('email', 'Email address', 'required|max_length[80]|valid_email', ['required' => 'Please enter your email address.', 'valid_email' => 'Please enter a valid email address.']);

        if (! $v->run()) {
            $result = ['error' => true, 'message' => (string) current($v->error_array()), 'email' => $email];
        } else {
            $added  = (new NewsletterModel())->subscribe($email);
            $result = match (true) {
                $added === true => ['error' => false, 'message' => 'Subscribed. Thank you.'],
                $added === null => ['error' => false, 'message' => 'This email address is already subscribed.'],
                default         => ['error' => true, 'message' => 'We could not add your email right now. Please try again later.', 'email' => $email],
            };
            $added === false && log_message('error', 'Home::subscribe could not save a newsletter subscription.');
        }

        session()->setFlashdata(self::NEWSLETTER_FLASH, $result);
        redirect_now($back);
    }

    /**
     * Hero slides: the active header images whose file exists.
     *
     * @return list<array{image: string, small: string, eyebrow: string, title: string, accent: string, text: string, url: string, external: bool}>
     */
    protected function slides(): array
    {
        $rows = (new HeaderImagesModel())->get_active_slides(self::SLIDE_COUNT);
        if ($rows === false) {
            log_message('error', 'Home: could not load the header images.');
        }

        $site   = strtolower((string) parse_url(base_url(), PHP_URL_HOST));
        $slides = [];
        foreach (is_array($rows) ? $rows : [] as $row) {
            if ($this->images->path(HeaderImages::IMAGE_FOLDER, $row['header_image']) === '') {
                continue;
            }
            [$title, $accent] = array_pad(array_map('trim', explode('|', (string) $row['line_two'], 2)), 2, '');
            $url      = trim((string) $row['header_url']);
            $slides[] = [
                'image'    => get_image(HeaderImages::IMAGE_FOLDER, $row['header_image'], 680, 680, 'C'),
                'small'    => get_image(HeaderImages::IMAGE_FOLDER, $row['header_image'], 400, 400, 'C'),
                'eyebrow'  => trim((string) $row['line_one']),
                'title'    => $title,
                'accent'   => $accent,
                'text'     => trim((string) $row['line_three']),
                'url'      => $url,
                'external' => $url !== '' && strtolower((string) parse_url($url, PHP_URL_HOST)) !== $site,
            ];
        }

        return $slides;
    }

    /**
     * Top-level public categories flagged "Home" (else all), with a card image.
     */
    protected function categories(): array
    {
        $rows = (new CategoryModel())->get_public_children(0);
        if (! is_array($rows)) {
            log_message('error', 'Home: could not load the categories.');

            return [];
        }
        $home = array_values(array_filter($rows, static fn ($row) => ($row['is_home'] ?? '0') === '1'));
        $rows = array_slice($home ?: $rows, 0, self::CATEGORY_COUNT);

        foreach ($rows as &$row) {
            $row['image_url'] = category_image_url($row, 600, 600);
        }

        return $rows;
    }

    /**
     * Products with a flag (Featured / New), else the newest.
     */
    protected function flagged(string $flag): array
    {
        $rows = $this->products->get_public_flagged($flag, self::PRODUCT_COUNT);

        return is_array($rows) ? $rows : [];
    }

    /**
     * Newest active testimonials (Manage Testimonials) with a photo URL ('' = initials).
     *
     * @return list<array{name: string, role: string, text: string, photo: string}>
     */
    protected function testimonials(): array
    {
        $rows = (new \Modules\Testimonials\Models\TestimonialModel())->get_public_testimonials(self::TESTIMONIAL_COUNT);
        if ($rows === false) {
            log_message('error', 'Home: could not load the testimonials.');
        }

        $out = [];
        foreach (is_array($rows) ? $rows : [] as $row) {
            $out[] = [
                'name'  => (string) $row['poster_name'],
                'role'  => (string) ($row['poster_role'] ?? ''),
                'text'  => (string) $row['testimonial_description'],
                'photo' => (string) $row['photo'] !== '' ? get_image('testimonial', $row['photo'], 120, 120, 'C') : '',
            ];
        }

        return $out;
    }

    /**
     * Partner logos (Manage Team & Partners, type Partner) whose image exists.
     *
     * @return list<array{name: string, logo: string}>
     */
    protected function partners(): array
    {
        $logos = [];
        foreach ((new TeamModel())->get_active(TeamModel::TYPE_PARTNER, self::PARTNER_COUNT) ?: [] as $row) {
            $logo = get_image('team', $row['photo'], 360, 200);
            $logo !== '' && $logos[] = ['name' => (string) $row['name'], 'logo' => $logo];
        }

        return $logos;
    }
}
