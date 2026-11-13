<?php

namespace App\Libraries;

use App\Models\SettingsModel;

/**
 * Website settings for every page (service('siteSettings')).
 *
 * Values come from tbl_site_settings, layered over the defaults below (the
 * site's original values), so a missing row or an unreachable database never
 * breaks a page — it just shows the defaults.
 */
class SiteSettings
{
    /**
     * ImageUpload folder of the uploaded settings images (uploads/settings/).
     */
    public const UPLOAD_FOLDER = 'settings';

    /**
     * Bundled logo used when no logo has been uploaded.
     */
    public const DEFAULT_LOGO = 'customcart-logo.png';

    /**
     * Favicon used when none has been uploaded (the one the public layout uses).
     */
    public const DEFAULT_FAVICON = 'favicon.ico';

    protected ?array $values = null;

    protected bool $from_database = false;

    /**
     * All rows read from tbl_site_settings (including keys not listed in defaults()).
     */
    protected array $stored = [];

    protected ?SettingsModel $model = null;

    public static function defaults(): array
    {
        return [
            'site_name'       => 'CustomCart',
            'site_title'      => 'CustomCart',
            'site_tagline'    => 'Your Store, Your Way',
            'site_logo'       => '',
            'site_favicon'    => 'favicon.ico',
            'contact_email'   => '',
            'contact_phone'   => '',
            'contact_address' => '',
            'office_hours'    => '',
            'company_license' => '',
            // Contact extras: social links, analytics, map, WhatsApp.
            'facebook_link'       => '',
            'twitter_link'        => '',
            'linkedin_link'       => '',
            'youtube_link'        => '',
            'instagram_link'      => '',
            'google_analytics_id' => '',
            'google_web_code'     => '',
            'map_code'            => '',
            'whatsapp_no'         => '',
            'whatsapp_text'       => '',
            'footer_copyright'    => '© {year} {site_name}. All rights reserved.',
            'footer_description'  => '',
            'meta_title'          => 'CustomCart – Complete E-Commerce Platform',
            'meta_description'    => 'CustomCart is a complete, customizable e-commerce platform to launch, manage and grow your online store. Your Store, Your Way.',
            'meta_keywords'       => 'CustomCart, e-commerce platform, online store, shopping cart, customizable e-commerce',
            'og_image'            => '',
            'timezone'            => 'Asia/Riyadh',
            'maintenance_mode'    => '0',
            // Online store (Sitepanel > Settings > Online store); read through shop_amounts().
            'shipping_charges'    => '0',
            'free_delivery_above' => '0',
            'gst_percent'         => '15',
            'low_stock_threshold' => '5',
        ];
    }

    /**
     * Online store amounts as numbers, the only source for every cart, checkout
     * and order calculation (ShopCart::summary()):
     *   shipping_charges    delivery charge of an order
     *   free_delivery_above subtotal from which delivery is free (0 = never free)
     *   gst_percent         VAT rate added on top of prices and delivery (0-100)
     * A stored value that is not a valid number falls back to its default.
     *
     * @return array{shipping_charges: float, free_delivery_above: float, gst_percent: float}
     */
    public function shop_amounts(): array
    {
        $amounts = [];

        foreach (['shipping_charges' => 1e7, 'free_delivery_above' => 1e7, 'gst_percent' => 100] as $key => $max) {
            $value = trim($this->get($key));
            if (! is_numeric($value) || (float) $value < 0 || (float) $value > $max) {
                $value = self::defaults()[$key];
            }
            $amounts[$key] = round((float) $value, 2);
        }

        return $amounts;
    }

    /**
     * Stock level at or under which a product counts as low stock
     * (Sitepanel > Settings > Online store; Manage Products low-stock filter).
     */
    public function low_stock_threshold(): int
    {
        $value = trim($this->get('low_stock_threshold'));

        return is_numeric($value) && (float) $value >= 0 ? (int) $value : (int) self::defaults()['low_stock_threshold'];
    }

    /**
     * @return array All settings (defaults filled in).
     */
    public function all(): array
    {
        if ($this->values === null) {
            $this->model ??= new SettingsModel();
            $stored = $this->model->all();

            $this->from_database = ($stored !== false);
            $this->stored        = $stored ?: [];
            $this->values        = array_merge(
                self::defaults(),
                $stored ? array_intersect_key($stored, self::defaults()) : [],
            );

            foreach (['site_name', 'site_title', 'timezone', 'footer_copyright'] as $required) {
                if (trim((string) $this->values[$required]) === '') {
                    $this->values[$required] = self::defaults()[$required];
                }
            }
        }

        return $this->values;
    }

    public function get(string $key): string
    {
        $all = $this->all();

        return isset($all[$key]) ? (string) $all[$key] : '';
    }

    /**
     * Stored rows whose key starts with $prefix, with the prefix removed —
     * read from the same single query as the settings above.
     *
     * @param string $prefix e.g. 'sp_theme_'
     */
    public function stored_with_prefix(string $prefix): array
    {
        $this->all();

        $found = [];

        foreach ($this->stored as $key => $value) {
            if (str_starts_with((string) $key, $prefix)) {
                $found[substr((string) $key, strlen($prefix))] = $value;
            }
        }

        return $found;
    }

    /**
     * Forget cached values (after a save).
     */
    public function refresh(): void
    {
        $this->values = null;
    }

    /**
     * Whether values were read from the database this request.
     */
    public function loaded_from_database(): bool
    {
        $this->all();

        return $this->from_database;
    }

    /**
     * Relative path (= URL path) of an uploaded settings image, e.g.
     * 'uploads/settings/<file>', or '' when unset or the file is missing.
     *
     * @param string $key site_logo | site_favicon | og_image
     */
    public function image_path(string $key): string
    {
        return (new ImageUpload())->path(self::UPLOAD_FOLDER, $this->get($key));
    }

    /**
     * Logo path, falling back to the bundled logo.
     */
    public function logo_path(): string
    {
        $path = $this->image_path('site_logo');

        return $path !== '' ? $path : self::DEFAULT_LOGO;
    }

    /**
     * Favicon path, falling back to the bundled favicon ('' if that is missing too).
     */
    public function favicon_path(): string
    {
        $path = $this->image_path('site_favicon');
        if ($path === '' && is_file(FCPATH . self::DEFAULT_FAVICON)) {
            $path = self::DEFAULT_FAVICON;
        }

        return $path;
    }

    /**
     * Footer copyright with {year} and {site_name} filled in (plain text).
     */
    public function copyright(): string
    {
        return strtr($this->get('footer_copyright'), [
            '{year}'      => date('Y'),
            '{site_name}' => $this->get('site_name'),
        ]);
    }

    public function maintenance_mode(): bool
    {
        return $this->get('maintenance_mode') === '1';
    }
}
