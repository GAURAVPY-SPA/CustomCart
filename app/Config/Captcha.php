<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Verification code ("captcha") of the website's public forms
 * (App\Libraries\Captcha, the image at GET captcha/<form>, the field
 * app/Views/includes/captcha.php and assets/designer/resources/js/captcha.js).
 *
 * The code is kept only in the session, one per form, and shown as an
 * image (never in the page). It is used once: a check, a refresh or a new
 * image replaces it.
 */
class Captcha extends BaseConfig
{
    /**
     * Forms that have a code (keys of the session codes and of the image URL).
     *
     * @var list<string>
     */
    public array $forms = ['contact', 'register', 'product_enquiry', 'testimonial', 'refer'];

    /**
     * Characters of a code: capital letters and digits without the 0/O and 1/I lookalikes (as in the design).
     */
    public string $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public int $length = 6;

    /**
     * Minutes a code stays valid after its image was shown.
     */
    public int $expiryMinutes = 30;

    /**
     * Image size in pixels (shown in the design's .captcha-code box, 52 px high).
     */
    public int $width  = 120;
    public int $height = 40;

    /**
     * Colour of the code's characters, as hex: #rrggbb or #rgb (default: the
     * design's dark red). The image is otherwise transparent, so choose a
     * colour that reads well on the white captcha box.
     *
     * Can also be set per server in .env, without editing this file:
     *   captcha.color = '#1D5FAF'
     *
     * An invalid value is logged and the default is used.
     */
    public string $color = '#233e82';

    /**
     * Colour of the light lines and dots drawn across the code (hex, as
     * $color; drawn semi-transparent). '' = the same colour as the code.
     *
     * .env: captcha.noiseColor = '#8A919C'
     */
    public string $noiseColor = '#0a1f3d';

    /**
     * Name of the form field holding the typed code.
     */
    public string $field = 'captcha_code';

    /**
     * Prefix of the session keys (captcha_<form>).
     */
    public string $sessionPrefix = 'captcha_';
}
