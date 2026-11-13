<?php

namespace Modules\Sitepanel\Libraries;

use Modules\Sitepanel\Config\Sitepanel;

/**
 * Sitepanel theme colours as CSS custom properties (service('sitepanelTheme')).
 *
 * Defaults: Modules\Sitepanel\Config\Sitepanel (theme section). Live values: tbl_site_settings
 * rows "sp_theme_<name>" (read with the site settings — no extra query).
 * Only valid hex colours are used; anything else falls back to the default.
 */
class SitepanelTheme
{
    public const SETTING_PREFIX = 'sp_theme_';
    public const HEX_COLOR      = '/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/';

    /**
     * Dashboard card gradient colours: #rgb or #rrggbb (no alpha, so the
     * text contrast can be worked out).
     */
    public const GRADIENT_COLOR = '/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/';

    /**
     * Gradient directions offered (CSS degrees) => label.
     */
    public const GRADIENT_ANGLES = [
        0   => 'Bottom to top',
        45  => 'Bottom left to top right',
        90  => 'Left to right',
        135 => 'Top left to bottom right',
        180 => 'Top to bottom',
        225 => 'Top right to bottom left',
        270 => 'Right to left',
        315 => 'Bottom right to top left',
    ];

    /**
     * Card text colours: the one with the better contrast on both ends wins.
     */
    public const TEXT_LIGHT = '#ffffff';
    public const TEXT_DARK  = '#1f2933';

    protected ?array $colors = null;

    protected ?array $gradients = null;

    /**
     * @return array name => colour (validated)
     */
    public function colors(): array
    {
        if ($this->colors === null) {
            $defaults = config(Sitepanel::class)->themeColors;
            $stored   = service('siteSettings')->stored_with_prefix(self::SETTING_PREFIX);

            $this->colors = $defaults;

            foreach ($defaults as $name => $default) {
                if (! isset($stored[$name]) || trim((string) $stored[$name]) === '') {
                    continue;
                }

                $value = trim((string) $stored[$name]);
                if (preg_match(self::HEX_COLOR, $value)) {
                    $this->colors[$name] = strtolower($value);
                } else {
                    log_message('error', 'Sitepanel theme: invalid colour for ' . self::SETTING_PREFIX . $name . '; using the default.');
                }
            }
        }

        return $this->colors;
    }

    /**
     * Forget cached colours (after a save).
     */
    public function refresh(): void
    {
        $this->colors    = null;
        $this->gradients = null;
    }

    /**
     * Dashboard card gradients (Config\Sitepanel::$themeStatGradients), with the
     * saved values where they are valid.
     *
     * @return array key => [label, start, end, angle, text, contrast]
     */
    public function gradients(): array
    {
        if ($this->gradients === null) {
            $stored = service('siteSettings')->stored_with_prefix(self::SETTING_PREFIX);

            $this->gradients = [];

            foreach (config(Sitepanel::class)->themeStatGradients as $key => $default) {
                $card = $default;

                foreach (['start', 'end'] as $stop) {
                    $value = trim((string) ($stored['stat-' . $key . '-' . $stop] ?? ''));
                    if ($value === '') {
                        continue;
                    }
                    if (preg_match(self::GRADIENT_COLOR, $value)) {
                        $card[$stop] = $value;
                    } else {
                        log_message('error', 'Sitepanel theme: invalid colour for ' . self::SETTING_PREFIX . 'stat-' . $key . '-' . $stop . '; using the default.');
                    }
                }

                $angle = trim((string) ($stored['stat-' . $key . '-angle'] ?? ''));
                if ($angle !== '') {
                    if (ctype_digit($angle) && isset(self::GRADIENT_ANGLES[(int) $angle])) {
                        $card['angle'] = (int) $angle;
                    } else {
                        log_message('error', 'Sitepanel theme: invalid angle for ' . self::SETTING_PREFIX . 'stat-' . $key . '-angle; using the default.');
                    }
                }

                $card['start'] = self::long_hex($card['start']);
                $card['end']   = self::long_hex($card['end']);
                [$card['text'], $card['contrast']] = self::text_for($card['start'], $card['end']);

                $this->gradients[$key] = $card;
            }
        }

        return $this->gradients;
    }

    /**
     * #rgb -> #rrggbb, lower case.
     */
    public static function long_hex(string $color): string
    {
        $color = strtolower($color);

        return strlen($color) === 4
            ? '#' . $color[1] . $color[1] . $color[2] . $color[2] . $color[3] . $color[3]
            : $color;
    }

    /**
     * Text colour for a gradient and its lowest contrast ratio over the two
     * ends (WCAG; 4.5 or more reads well).
     *
     * @return array{0: string, 1: float}
     */
    public static function text_for(string $start, string $end): array
    {
        $best = [self::TEXT_LIGHT, 0.0];

        foreach ([self::TEXT_LIGHT, self::TEXT_DARK] as $text) {
            $ratio = min(self::contrast($text, $start), self::contrast($text, $end));
            if ($ratio > $best[1]) {
                $best = [$text, $ratio];
            }
        }

        return [$best[0], round($best[1], 2)];
    }

    /**
     * WCAG contrast ratio of two #rrggbb colours.
     */
    public static function contrast(string $a, string $b): float
    {
        $la = self::luminance($a);
        $lb = self::luminance($b);

        return (max($la, $lb) + 0.05) / (min($la, $lb) + 0.05);
    }

    protected static function luminance(string $hex): float
    {
        $channels = array_map(static function (string $pair): float {
            $c = hexdec($pair) / 255;

            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        }, str_split(substr($hex, 1, 6), 2));

        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    }

    /**
     * The theme as a :root declaration block plus one rule per dashboard
     * card gradient. Safe to print inside <style>: names and card keys come
     * from the config file, values are validated hex colours and angles.
     */
    public function css(): string
    {
        $rules = [];

        foreach ($this->colors() as $name => $value) {
            $rules[] = '--sp-' . $name . ':' . $value;
        }

        $css = ':root{' . implode(';', $rules) . '}';

        foreach ($this->gradients() as $key => $g) {
            $css .= '.sp-stat--g-' . $key . '{--sp-g-start:' . $g['start'] . ';--sp-g-end:' . $g['end']
                . ';--sp-g-angle:' . $g['angle'] . 'deg;--sp-g-text:' . $g['text'] . '}';
        }

        return $css;
    }

    /**
     * Return a complete sitepanel palette for a preset.
     *
     * @return array|false setting suffix => validated color
     */
    public function preset_colors(string $theme_id)
    {
        $presets = config(Sitepanel::class)->themePresets;
        if (! isset($presets[$theme_id])) {
            return false;
        }

        $preset     = $presets[$theme_id];
        $p          = $preset['palette'];
        $soft       = $preset['soft_primary'];
        $focus_ring = $p['primary'] . '33';

        return [
            'bg'                         => $p['background'],
            'panel'                      => $p['surface'],
            'dashboard-card-bg'          => $p['card'] ?? $p['surface'],
            'line'                       => $p['border'],
            'ink'                        => $p['text'],
            'muted'                      => $p['muted'],
            'brand'                      => $p['primary'],
            'accent'                     => $p['accent'],
            'brand-soft'                 => $soft,
            'logo-bg'                    => $p['surface'],
            'table-hover'                => $p['background'],
            'breadcrumb-sep'             => $p['muted'],
            'sidebar'                    => $p['secondary'],
            'sidebar-text'               => $preset['sidebar_text'],
            'sidebar-icon'               => $preset['sidebar_text'],
            'sidebar-muted'              => $preset['sidebar_muted'],
            'sidebar-hover'              => $preset['sidebar_hover'],
            'sidebar-hover-text'         => '#FFFFFF',
            'sidebar-active'             => $preset['sidebar_hover'],
            'sidebar-active-text'        => '#FFFFFF',
            'header-bg'                  => $p['surface'],
            'header-text'                => $p['text'],
            'btn-primary-bg'             => $p['primary'],
            'btn-primary-text'           => '#FFFFFF',
            'btn-primary-hover-bg'       => $preset['primary_hover'],
            'btn-primary-hover-text'     => '#FFFFFF',
            'btn-secondary-bg'           => $p['surface'],
            'btn-secondary-text'         => $p['primary'],
            'btn-secondary-border'       => $p['primary'],
            'btn-secondary-hover-bg'     => $soft,
            'btn-secondary-hover-text'   => $p['secondary'],
            'btn-secondary-hover-border' => $p['secondary'],
            'link'                       => $p['primary'],
            'link-hover'                 => $preset['primary_hover'],
            'field-border'               => $p['border'],
            'field-focus-border'         => $p['primary'],
            'field-focus-ring'           => $focus_ring,
            'focus-outline'              => $p['accent'],
            'ok'                         => $p['success'],
            'ok-soft'                    => $preset['soft_success'],
            'warn'                       => '#9A6700',
            'warn-soft'                  => '#FFF4D6',
            'error'                      => $p['danger'],
            'error-soft'                 => $preset['soft_danger'],
            'alert-success-text'         => '#14532D',
            'alert-success-bg'           => $preset['soft_success'],
            'alert-success-border'       => '#BBF7D0',
            'alert-danger-text'          => '#7F1D1D',
            'alert-danger-bg'            => $preset['soft_danger'],
            'alert-danger-border'        => '#FECACA',
            'alert-info-text'            => '#055160',
            'alert-info-bg'              => '#CFF4FC',
            'alert-info-border'          => '#9EEAF9',
        ];
    }
}
