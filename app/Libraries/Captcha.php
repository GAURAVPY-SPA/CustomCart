<?php

namespace App\Libraries;

use Config\Captcha as CaptchaConfig;

/**
 * Verification code ("captcha") shared by the website's public forms
 * (Config\Captcha::$forms: Contact Us, Registration, Product Inquiry).
 *
 * - image():     a new code for a form, kept in the session (captcha_<form>:
 *                code + time), returned as a PNG (GET captcha/<form>,
 *                App\Controllers\Captcha). The code never appears in the page.
 * - add_rule():  the form's validation rule for the typed code (field
 *                Config\Captcha::$field): required, then check().
 * - check():     compares the typed code (any case) with the form's code while
 *                it is valid, and removes it: a code is used once.
 * - field data:  the view partial app/Views/includes/captcha.php shows the
 *                image, the refresh button and the input; page_script() is its
 *                script (assets/designer/resources/js/captcha.js), which
 *                reloads the image without reloading the page.
 *
 * Every image request replaces the form's code, so a refreshed (or
 * re-shown) image makes the previous code invalid.
 */
class Captcha
{
    /**
     * Code colour used when Config\Captcha::$color is not a valid hex colour.
     */
    public const DEFAULT_COLOR = '#A8121A';

    protected CaptchaConfig $config;

    public function __construct(?CaptchaConfig $config = null)
    {
        $this->config = $config ?? config(CaptchaConfig::class);
    }

    public function is_form(string $form): bool
    {
        return in_array($form, $this->config->forms, true);
    }

    /**
     * A new code for the form (kept in the session), drawn as a PNG.
     *
     * @return string PNG data ('' for an unknown form or when the image could not be made)
     */
    public function image(string $form): string
    {
        if (! $this->is_form($form)) {
            return '';
        }

        $code = '';
        $last = strlen($this->config->characters) - 1;
        for ($i = 0; $i < $this->config->length; $i++) {
            $code .= $this->config->characters[random_int(0, $last)];
        }
        session()->set($this->key($form), ['code' => $code, 'time' => time()]);

        return $this->draw($code);
    }

    /**
     * Whether $input is the form's current code (letter case ignored). The
     * code is removed whatever the result, so it cannot be tried again.
     */
    public function check(string $form, string $input): bool
    {
        if (! $this->is_form($form)) {
            return false;
        }

        $saved = session($this->key($form));
        session()->remove($this->key($form));

        return is_array($saved)
            && isset($saved['code'], $saved['time'])
            && time() - (int) $saved['time'] <= $this->config->expiryMinutes * 60
            && hash_equals((string) $saved['code'], strtoupper(trim($input)));
    }

    /**
     * The verification code rule of a form, on the posted field Config\Captcha::$field.
     */
    public function add_rule(FormValidation $v, string $form, string $label = 'Verification code'): void
    {
        $v->set_rules(
            $this->config->field,
            $label,
            ['required', ['captcha_matches', fn ($value) => (string) $value === '' || $this->check($form, (string) $value)]],
            [
                'required'        => 'Please enter the verification code.',
                'captcha_matches' => 'The verification code does not match. Please enter the new code shown.',
            ],
        );
    }

    /**
     * Name of the posted field.
     */
    public function field(): string
    {
        return $this->config->field;
    }

    /**
     * Address of a form's code image.
     */
    public function image_url(string $form): string
    {
        return site_url('captcha/' . $form);
    }

    /**
     * Data for the view partial app/Views/includes/captcha.php.
     *
     * @param string $form  Config\Captcha::$forms key
     * @param string $id    Prefix of the element ids (unique on the page)
     * @param array  $extra label, error (message shown under the field), layout ('row' | 'split')
     */
    public function view_data(string $form, string $id, array $extra = []): array
    {
        return ['captcha' => $extra + [
            'form'      => $form,
            'id'        => $id,
            'field'     => $this->config->field,
            'image_url' => $this->image_url($form),
            'width'     => $this->config->width,
            'height'    => $this->config->height,
            'length'    => $this->config->length,
            'label'     => 'Verification Code',
            'error'     => '',
            'layout'    => 'row',
        ]];
    }

    /**
     * The refresh script, for a page's $page_scripts.
     *
     * @return array{src: string, defer: bool}
     */
    public static function page_script(): array
    {
        return ['src' => asset_url('assets/designer/resources/js/captcha.js'), 'defer' => true];
    }

    protected function key(string $form): string
    {
        return $this->config->sessionPrefix . $form;
    }

    /**
     * The code drawn in the design's colour on a transparent image: each
     * character from GD's built-in font, enlarged, slightly tilted and
     * moved, with a few light lines and dots across.
     */
    protected function draw(string $code): string
    {
        if (! function_exists('imagecreatetruecolor')) {
            log_message('error', 'Captcha: the GD extension is not available.');

            return '';
        }

        [$w, $h] = [$this->config->width, $this->config->height];
        $image   = imagecreatetruecolor($w, $h);
        imagesavealpha($image, true);
        imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));
        [$r, $g, $b] = $this->rgb($this->config->color, '$color', self::DEFAULT_COLOR);

        $step = intdiv($w - 8, strlen($code));
        foreach (str_split($code) as $i => $char) {
            // 9 x 15 px glyph, drawn twice for weight, then enlarged 2x and tilted.
            $glyph = imagecreatetruecolor(12, 16);
            imagesavealpha($glyph, true);
            imagefill($glyph, 0, 0, imagecolorallocatealpha($glyph, 0, 0, 0, 127));
            $ink = imagecolorallocate($glyph, $r, $g, $b);
            imagestring($glyph, 5, 1, 0, $char, $ink);
            imagestring($glyph, 5, 2, 0, $char, $ink);

            $big = imagecreatetruecolor(24, 32);
            imagesavealpha($big, true);
            imagealphablending($big, false);
            imagefill($big, 0, 0, imagecolorallocatealpha($big, 0, 0, 0, 127));
            imagecopyresampled($big, $glyph, 0, 0, 0, 0, 24, 32, 12, 16);
            $tilted = imagerotate($big, random_int(-14, 14), imagecolorallocatealpha($big, 0, 0, 0, 127));

            imagecopy($image, $tilted, 4 + $i * $step + random_int(-1, 2), max(0, intdiv($h - imagesy($tilted), 2) + random_int(-3, 3)), 0, 0, imagesx($tilted), imagesy($tilted));
        }

        [$nr, $ng, $nb] = trim($this->config->noiseColor) === '' ? [$r, $g, $b] : $this->rgb($this->config->noiseColor, '$noiseColor', sprintf('#%02x%02x%02x', $r, $g, $b));
        $noise          = imagecolorallocatealpha($image, $nr, $ng, $nb, 85);
        for ($i = 0; $i < 3; $i++) {
            imageline($image, 0, random_int(4, $h - 4), $w, random_int(4, $h - 4), $noise);
        }
        for ($i = 0; $i < 40; $i++) {
            imagesetpixel($image, random_int(0, $w - 1), random_int(0, $h - 1), $noise);
        }

        ob_start();
        imagepng($image);

        return (string) ob_get_clean();
    }

    /**
     * A hex colour (#rrggbb or #rgb) as [r, g, b]; an invalid one is logged and $fallback is used.
     *
     * @return array{0: int, 1: int, 2: int}
     */
    protected function rgb(string $color, string $setting, string $fallback): array
    {
        $hex = ltrim(trim($color), '#');
        if (! preg_match('/^(?:[0-9a-f]{3}|[0-9a-f]{6})$/iD', $hex)) {
            log_message('error', 'Captcha: Config\Captcha::' . $setting . ' "' . $color . '" is not a hex colour (#rrggbb or #rgb); ' . $fallback . ' is used.');
            $hex = ltrim($fallback, '#');
        }
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        return array_map('hexdec', str_split($hex, 2));
    }
}
