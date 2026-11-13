<?php

namespace Modules\Sitepanel\Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Every Sitepanel setting, in one place: config(\Modules\Sitepanel\Config\Sitepanel::class).
 *
 * Sections: Admin password, Banners, Dashboard, Theme (colours, dashboard
 * card gradients, presets). Add a new Sitepanel setting as a property in the
 * section it belongs to (or a new section at the end), with its default and
 * a short comment; give it the section's prefix so names stay unambiguous.
 *
 * Every scalar value can be set in .env as sitepanel.<property>, e.g.
 *   sitepanel.passwordMinLength = 10
 *   sitepanel.showDashboardOrdersGraph = false
 *
 * (Routes.php and Services.php in this folder are the module's route and
 * service registrations, not settings.)
 */
class Sitepanel extends BaseConfig
{
    /*
     * --------------------------------------------------------------------
     * Admin password
     * --------------------------------------------------------------------
     * Rules for every new Sitepanel administrator password, checked by
     * Modules\Sitepanel\Libraries\AdminPasswordPolicy wherever one is set:
     * Sitepanel > Change Password, Manage Subadmins (add / new password),
     * php spark sitepanel:create-admin and php spark admin:reset-password.
     * Signing in is not affected: an existing password keeps working even when
     * it doesn't meet rules made stricter later.
     */

    /**
     * Fewest characters (at least 1).
     */
    public int $passwordMinLength = 8;

    /**
     * Most characters (not below passwordMinLength).
     */
    public int $passwordMaxLength = 255;

    /**
     * At least one letter A-Z or a-z.
     */
    public bool $passwordRequireLetter = true;

    /**
     * At least one digit 0-9.
     */
    public bool $passwordRequireNumber = true;

    /**
     * At least one character that is not a letter, digit or space, e.g. @ # $ ! % &.
     */
    public bool $passwordRequireSpecialCharacter = true;

    /*
     * --------------------------------------------------------------------
     * Banners (CodeIgniter 3: modules/sitepanel/config/banners.php)
     * --------------------------------------------------------------------
     */

    /**
     * Banner positions offered in Sitepanel > Manage Banners: position => best
     * image size shown as a hint ('' = no hint). From the reference project's
     * banner configuration ("bannersz") and its stored "Inner Pages Top" banner.
     * Banners are stored per position; add positions here as the public site
     * gets places to show them.
     *
     * The hero image of an inner page (app/Views/inner_hero.php, inner_banner())
     * is the newest active banner of the page's own position, else of
     * "Inner Pages Top" (every inner page). A static page's position is its
     * page name, then its parent page's name (Pages::banner_positions()), so
     * a static page added later can get its own position here by that name.
     *
     * @var array<string, string>
     */
    public array $bannerPositions = [
        'Inner Pages Top' => '1920x300',
        // Inner pages
        'About Us'                                       => '1920x300',
        'Our Team'                                       => '1920x300',
        'Partners'                                       => '1920x300',
        'Industries'                                     => '1920x300',
        'Certificates'                                   => '1920x300',
        'Brochures'                                      => '1920x300',
        'FAQ'                                            => '1920x300',
        'Category Pages'                                 => '1920x300', // a banner chosen per category
        'Testimonials'                                   => '1920x300',
        'Images Gallery'                                 => '1920x300',
        'Videos Gallery'                                 => '1920x300',
        'News & Events'                                  => '1920x300',
        'Online Store'                                   => '1920x300',
        'Login & Register'                               => '1920x300',
        'My Account'                                     => '1920x300',
        'Contact Us'                                     => '1920x300',
        'Thank You'                                      => '1920x300', // Contact Us after a message is sent
        'Sitemap'                                        => '1920x300',
        'Privacy Policy'                                 => '1920x300',
        'Terms & Conditions'                             => '1920x300',
        'Returns'                                        => '1920x300',
        'Refunds'                                        => '1920x300',
        '404 Page Not Found'                             => '1920x300',
        // Other places
        'Left Panel'  => '300x135',
        'Page Bottom' => '645x80',
    ];

    /*
     * --------------------------------------------------------------------
     * Dashboard (Modules\Sitepanel\Controllers\Dashboard)
     * --------------------------------------------------------------------
     */

    /**
     * Show the Orders graph (Daily / Monthly / Yearly). FALSE leaves the whole
     * section out and its order queries are not run. Admins without access to
     * Manage Orders never see it.
     */
    public bool $showDashboardOrdersGraph = true;

    /*
     * --------------------------------------------------------------------
     * Theme (CodeIgniter 3: modules/sitepanel/config/theme.php, theme_presets.php)
     * --------------------------------------------------------------------
     * Read by Modules\Sitepanel\Libraries\SitepanelTheme and Sitepanel > Theme.
     */

    /**
     * Sitepanel theme colours — the single source of the default values.
     *
     * Each key becomes the CSS custom property --sp-<key>, printed once per
     * sitepanel page (login + layout) and used throughout
     * assets/sitepanel/css/sitepanel.css. The public website does not use these.
     *
     * To change a colour, set it in MySQL (no code change, no CSS edit):
     *   UPDATE tbl_site_settings SET setting_value = '#123456'
     *   WHERE setting_key = 'sp_theme_sidebar';
     * Accepted values: #rgb, #rrggbb or #rrggbbaa (alpha). Anything else, a
     * missing row or an empty value falls back to the default below.
     * See apps/sql/005_sitepanel_theme.sql for the full list of rows.
     *
     * @var array<string, string>
     */
    public array $themeColors = [
        // Page, panels, cards, dividers
        'bg'                        => '#f3f5f7', // page background
        'panel'                     => '#ffffff', // cards / panels / footer / dropdown background
        'line'                      => '#e4e7eb', // card borders, dividers, table lines
        'ink'                       => '#1f2933', // main text
        'muted'                     => '#5f6b7a', // secondary text, labels, table headers
        'brand'                     => '#0b5a55', // brand text: summary-card icons, login subtitle
        'accent'                    => '#4fbf8f', // panel-title bar, active-menu marker
        'brand-soft'                => '#e6f2f0', // summary-card icon background
        'dashboard-card-bg'         => '#ffffff', // dashboard summary-card background (set by a preset's 'card' colour, or on its own in Theme Management)
        'logo-bg'                   => '#faf9f5', // area behind the logo (matches the logo file)
        'table-hover'               => '#f9fafb', // table row hover
        'breadcrumb-sep'            => '#9aa5b1', // breadcrumb "/" separator

        // Left sidebar
        'sidebar'                   => '#13252a', // background
        'sidebar-text'              => '#c5d2d3', // menu text
        'sidebar-icon'              => '#c5d2d3', // menu icons
        'sidebar-muted'             => '#7f9496', // section headings ("GENERAL", "ACCOUNT")
        'sidebar-hover'             => '#1d3539', // hover background
        'sidebar-hover-text'        => '#ffffff', // hover text + icon
        'sidebar-active'            => '#1d3539', // active item background
        'sidebar-active-text'       => '#ffffff', // active item text + icon

        // Top header
        'header-bg'                 => '#ffffff', // header bar + its buttons
        'header-text'               => '#1f2933', // page title + header buttons

        // Buttons
        'btn-primary-bg'            => '#0b5a55',
        'btn-primary-text'          => '#ffffff',
        'btn-primary-hover-bg'      => '#08423f',
        'btn-primary-hover-text'    => '#ffffff',
        'btn-secondary-bg'          => '#ffffff',
        'btn-secondary-text'        => '#0b5a55',
        'btn-secondary-border'      => '#0b5a55',
        'btn-secondary-hover-bg'    => '#e6f2f0',
        'btn-secondary-hover-text'  => '#08423f',
        'btn-secondary-hover-border'=> '#08423f',

        // Links
        'link'                      => '#0b5a55',
        'link-hover'                => '#0b5a55', // same as link today (links keep their colour on hover)

        // Form fields
        'field-border'              => '#dee2e6', // inputs, textareas, selects, checkboxes
        'field-focus-border'        => '#0b5a55', // text input / textarea focus border
        'field-focus-ring'          => '#0b5a5533', // text input / textarea focus glow (20% alpha)
        'focus-outline'             => '#4fbf8f', // keyboard focus outline

        // Status (badges, summary-card states)
        'ok'                        => '#1a7f4e',
        'ok-soft'                   => '#e3f4ea',
        'warn'                      => '#9a6700',
        'warn-soft'                 => '#fff4d6',
        'error'                     => '#b42318',
        'error-soft'                => '#fde8e6',

        // Alert messages (success / error / info notices)
        'alert-success-text'        => '#0a3622',
        'alert-success-bg'          => '#d1e7dd',
        'alert-success-border'      => '#a3cfbb',
        'alert-danger-text'         => '#58151c',
        'alert-danger-bg'           => '#f8d7da',
        'alert-danger-border'       => '#f1aeb5',
        'alert-info-text'           => '#055160',
        'alert-info-bg'             => '#cff4fc',
        'alert-info-border'         => '#9eeaf9',
    ];

    /**
     * Dashboard summary-card gradients, keyed by the card's sitepanel section
     * (Dashboard::summary_cards()). Edited in Theme Management; stored as
     * tbl_site_settings rows sp_theme_stat-<key>-start / -end / -angle. A
     * missing, empty or invalid row falls back to the default below. Theme
     * presets do not change these. Text on the card is white or dark,
     * whichever reads better on the chosen colours.
     *
     * start / end: #rgb or #rrggbb (no alpha). angle: one of
     * SitepanelTheme::GRADIENT_ANGLES (CSS degrees, 90 = left to right).
     *
     * @var array<string, array{label: string, start: string, end: string, angle: int}>
     */
    public array $themeStatGradients = [
        'category'    => ['label' => 'Categories', 'start' => '#2b6cb0', 'end' => '#1c4f86', 'angle' => 135],
        'products'    => ['label' => 'Products', 'start' => '#0b5a55', 'end' => '#073f3c', 'angle' => 135],
        'members'     => ['label' => 'Members', 'start' => '#2f7d4f', 'end' => '#1d5a37', 'angle' => 135],
        'orders'      => ['label' => 'Orders', 'start' => '#b7791f', 'end' => '#8a5a12', 'angle' => 135],
        'enquiry'     => ['label' => 'Enquiries', 'start' => '#c8501a', 'end' => '#a33a0c', 'angle' => 135],
        'testimonial' => ['label' => 'Testimonials', 'start' => '#11806f', 'end' => '#0a5c51', 'angle' => 135],
        'revenue'     => ['label' => 'Revenue', 'start' => '#7c3aed', 'end' => '#5b21b6', 'angle' => 135],
        'lowstock'    => ['label' => 'Low Stock', 'start' => '#f97316', 'end' => '#c2410c', 'angle' => 135],
    ];

    /**
     * Theme presets offered in Sitepanel > Theme (one click sets the colours).
     *
     * @var array<string, array>
     */
    public array $themePresets = [
        'customcart' => [
            'name' => 'CustomCart',
            'palette' => [
                'primary' => '#2563EB',
                'secondary' => '#172554',
                'accent' => '#F59E0B',
                'background' => '#F1F5F9',
                'surface' => '#FFFFFF',
                'card' => '#FFFFFF', // dashboard summary cards
                'text' => '#0F172A',
                'muted' => '#64748B',
                'border' => '#E2E8F0',
                'success' => '#16A34A',
                'danger' => '#DC2626',
            ],
            'sidebar_hover' => '#1E3A8A',
            'sidebar_text' => '#C7D2FE',
            'sidebar_muted' => '#8B9BC9',
            'primary_hover' => '#1D4ED8',
            'soft_primary' => '#EFF6FF',
            'soft_success' => '#F0FDF4',
            'soft_danger' => '#FEF2F2',
        ],
        'classic-blue' => [
            'name' => 'Classic Blue',
            'palette' => [
                'primary' => '#2563EB',
                'secondary' => '#1E293B',
                'accent' => '#0EA5E9',
                'background' => '#F8FAFC',
                'surface' => '#FFFFFF',
                'card' => '#FFFFFF', // dashboard summary cards
                'text' => '#0F172A',
                'muted' => '#64748B',
                'border' => '#E2E8F0',
                'success' => '#16A34A',
                'danger' => '#DC2626',
            ],
            'sidebar_hover' => '#334155',
            'sidebar_text' => '#CBD5E1',
            'sidebar_muted' => '#94A3B8',
            'primary_hover' => '#1D4ED8',
            'soft_primary' => '#EFF6FF',
            'soft_success' => '#F0FDF4',
            'soft_danger' => '#FEF2F2',
        ],
        'modern-emerald' => [
            'name' => 'Modern Emerald',
            'palette' => [
                'primary' => '#055841',
                'secondary' => '#04271e',
                'accent' => '#D97706',
                'background' => '#F8FAFC',
                'surface' => '#FFFFFF',
                'card' => '#FFFFFF', // dashboard summary cards
                'text' => '#1F2937',
                'muted' => '#6B7280',
                'border' => '#E5E7EB',
                'success' => '#15803D',
                'danger' => '#DC2626',
            ],
            'sidebar_hover' => '#0B654C',
            'sidebar_text' => '#B7E4D3',
            'sidebar_muted' => '#9DCDBB',
            'primary_hover' => '#065F46',
            'soft_primary' => '#ECFDF5',
            'soft_success' => '#F0FDF4',
            'soft_danger' => '#FEF2F2',
        ],
        'sunset-terracotta' => [
            'name' => 'Sunset Terracotta',
            'palette' => [
                'primary' => '#C2410C',
                'secondary' => '#431407',
                'accent' => '#D97706',
                'background' => '#FFF7ED',
                'surface' => '#FFFFFF',
                'card' => '#FFFFFF', // dashboard summary cards
                'text' => '#1C1917',
                'muted' => '#57534E',
                'border' => '#E7E5E4',
                'success' => '#15803D',
                'danger' => '#B91C1C',
            ],
            'sidebar_hover' => '#7C2D12',
            'sidebar_text' => '#FED7AA',
            'sidebar_muted' => '#FDBA74',
            'primary_hover' => '#9A3412',
            'soft_primary' => '#FFEDD5',
            'soft_success' => '#F0FDF4',
            'soft_danger' => '#FEF2F2',
        ],
        'royal-indigo' => [
            'name' => 'Royal Indigo',
            'palette' => [
                'primary' => '#4F46E5',
                'secondary' => '#1E1B4B',
                'accent' => '#C026D3',
                'background' => '#F5F3FF',
                'surface' => '#FFFFFF',
                'card' => '#FFFFFF', // dashboard summary cards
                'text' => '#1E1B4B',
                'muted' => '#5B5B78',
                'border' => '#DDD6FE',
                'success' => '#15803D',
                'danger' => '#DC2626',
            ],
            'sidebar_hover' => '#312E81',
            'sidebar_text' => '#DDD6FE',
            'sidebar_muted' => '#A5B4FC',
            'primary_hover' => '#4338CA',
            'soft_primary' => '#EEF2FF',
            'soft_success' => '#F0FDF4',
            'soft_danger' => '#FEF2F2',
        ],
    ];
}
