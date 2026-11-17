<?php

namespace Modules\Sitepanel\Controllers;

use App\Controllers\AdminController;
use App\Models\SettingsModel;
use Modules\Sitepanel\Config\Sitepanel as SitepanelConfig;
use Modules\Sitepanel\Libraries\AdminAccess;
use Modules\Sitepanel\Libraries\SitepanelTheme;

/**
 * Hidden theme preset manager. With no tbl_admin_sections row, the shared
 * AdminController limits direct access to super administrators.
 */
class Theme extends AdminController
{
    /**
     * Theme colour (Config\Sitepanel::$themeColors key) of the dashboard cards before they had
     * gradients; still set by presets, kept as the fallback background.
     */
    public const CARD_BG = 'dashboard-card-bg';

    protected string $base = 'sitepanel/theme';

    /**
     * Theme colours (Config\Sitepanel::$themeColors keys) editable one by one in the
     * "Custom colors" form, grouped for the view. A preset overwrites them.
     */
    protected array $custom_colors = [
        'Left sidebar (sp-sidebar)' => [
            'sidebar'        => 'Background',
            'sidebar-hover'  => 'Hover background',
            'sidebar-active' => 'Selected item background',
        ],
        'Primary buttons (btn sp-btn-primary)' => [
            'btn-primary-bg'         => 'Background',
            'btn-primary-text'       => 'Text',
            'btn-primary-hover-bg'   => 'Hover background',
            'btn-primary-hover-text' => 'Hover text',
        ],
        'Outline buttons (btn sp-btn-outline)' => [
            'btn-secondary-bg'           => 'Background',
            'btn-secondary-text'         => 'Text',
            'btn-secondary-border'       => 'Border',
            'btn-secondary-hover-bg'     => 'Hover background',
            'btn-secondary-hover-text'   => 'Hover text',
            'btn-secondary-hover-border' => 'Hover border',
        ],
    ];

    protected SitepanelTheme $sitepanel_theme;
    protected SettingsModel $settings_model;

    protected function init(): void
    {
        $this->sitepanel_theme = service('sitepanelTheme');
        $this->settings_model  = new SettingsModel();
    }

    public function index(): string
    {
        $errors  = [];
        $presets = config(SitepanelConfig::class)->themePresets;
        $posted  = []; // custom colours as entered, shown again after an error
        $posted_gradients = []; // card gradients as entered, shown again after an error

        if ($this->request->getMethod() === 'POST') {
            if (! $this->can(AdminAccess::PRIV_EDIT)) {
                $this->session->setFlashdata('sitepanel_error', "You don't have permission to change the theme.");
                redirect_now($this->base);
            }

            if ($this->request->getPost('form') === 'stat_gradients') {
                [$errors, $posted_gradients] = $this->save_gradients();
            } elseif ($this->request->getPost('form') === 'custom_colors') {
                [$errors, $posted] = $this->save_custom_colors();
            } else {
                $errors = $this->apply_preset($presets);
            }
        }

        $stored       = $this->settings_model->all();
        $active_theme = is_array($stored) && isset($stored['sp_active_theme'])
            && isset($presets[$stored['sp_active_theme']])
            ? $stored['sp_active_theme']
            : 'custom';
        $sitepanel = $this->sitepanel_theme->colors();
        $custom    = [];

        foreach ($this->custom_colors as $group => $fields) {
            foreach ($fields as $name => $label) {
                $value                 = $posted[$name] ?? $sitepanel[$name];
                $custom[$group][$name] = [
                    'label'  => $label,
                    'value'  => $value,
                    'picker' => $this->picker_value($value, $sitepanel[$name]),
                ];
            }
        }

        $current_palette = [
            'Sitepanel primary'    => $sitepanel['btn-primary-bg'],
            'Sitepanel secondary'  => $sitepanel['sidebar'],
            'Sitepanel accent'     => $sitepanel['accent'],
            'Sitepanel background' => $sitepanel['bg'],
            'Sitepanel surface'    => $sitepanel['panel'],
            'Sitepanel text'       => $sitepanel['ink'],
            'Sitepanel muted text' => $sitepanel['muted'],
            'Sitepanel border'     => $sitepanel['line'],
            'Success'              => $sitepanel['ok'],
            'Danger'               => $sitepanel['error'],
        ];

        return $this->render('theme/theme_manager_view', [
            'heading_title'   => 'Theme Management',
            'breadcrumbs'     => ['Theme Management' => null],
            'errors'          => $errors,
            'presets'         => $presets,
            'active_theme'    => $active_theme,
            'current_palette' => $current_palette,
            'custom'          => $custom,
            'gradients'       => $this->gradient_fields($posted_gradients),
            'angles'          => SitepanelTheme::GRADIENT_ANGLES,
            'can_edit'        => $this->can(AdminAccess::PRIV_EDIT),
        ]);
    }

    /**
     * Save the "Custom colors" form (only the sp_theme_* rows of
     * $custom_colors). Redirects on success.
     *
     * @return array{0: list<string>, 1: array} errors, colours as entered (name => value)
     */
    protected function save_custom_colors(): array
    {
        $input  = (array) $this->request->getPost('colors');
        $errors = [];
        $posted = [];
        $values = [];

        foreach ($this->custom_colors as $group => $fields) {
            foreach ($fields as $name => $label) {
                $posted[$name] = isset($input[$name]) ? trim((string) $input[$name]) : '';
                if (preg_match(SitepanelTheme::HEX_COLOR, $posted[$name])) {
                    $values[SitepanelTheme::SETTING_PREFIX . $name] = strtolower($posted[$name]);
                } else {
                    $errors[] = ($name === self::CARD_BG ? $label : $group . ' — ' . $label) . ': enter a hex color, e.g. #ffffff.';
                }
            }
        }

        if (! $errors) {
            if ($this->settings_model->save($values, $this->admin['admin_id'])) {
                $this->site_settings->refresh();
                $this->session->setFlashdata('sitepanel_success', 'Custom colors saved.');
                redirect_now($this->base);
            }
            $errors[] = 'The custom colors could not be saved. Please try again.';
        }

        return [$errors, $posted];
    }

    /**
     * Apply the posted preset (theme_id): all its colours and sp_active_theme.
     * Redirects on success.
     *
     * @param array $presets Config\Sitepanel::$themePresets
     *
     * @return list<string> Errors
     */
    protected function apply_preset(array $presets): array
    {
        $theme_id = (string) $this->request->getPost('theme_id');
        $colors   = $this->sitepanel_theme->preset_colors($theme_id);
        if ($colors === false || ! isset($presets[$theme_id])) {
            return ['Choose one of the available themes before applying it.'];
        }

        $values = [];

        foreach ($colors as $name => $value) {
            $values[SitepanelTheme::SETTING_PREFIX . $name] = $value;
        }
        $values['sp_active_theme'] = $theme_id;

        if ($this->settings_model->save($values, $this->admin['admin_id'])) {
            $this->site_settings->refresh();
            $this->session->setFlashdata('sitepanel_success', $presets[$theme_id]['name'] . ' applied.');
            redirect_now($this->base);
        }

        return ['The theme could not be saved. Your current colors have not been intentionally changed; please try again.'];
    }

    /**
     * Save the dashboard card gradients, or reset one card (button
     * reset_card=<key>) by emptying its rows so the defaults apply. Only the
     * sp_theme_stat-* rows are written. Redirects on success.
     *
     * @return array{0: list<string>, 1: array} errors, values as entered
     */
    protected function save_gradients(): array
    {
        $cards  = config(SitepanelConfig::class)->themeStatGradients;
        $prefix = SitepanelTheme::SETTING_PREFIX . 'stat-';
        $reset  = (string) $this->request->getPost('reset_card');

        if ($reset !== '') {
            if (! isset($cards[$reset])) {
                return [['Choose a dashboard card to reset.'], []];
            }
            $values = [$prefix . $reset . '-start' => '', $prefix . $reset . '-end' => '', $prefix . $reset . '-angle' => ''];
            if ($this->settings_model->save($values, $this->admin['admin_id'])) {
                $this->site_settings->refresh();
                $this->session->setFlashdata('sitepanel_success', $cards[$reset]['label'] . ' card gradient reset to its default.');
                redirect_now($this->base);
            }

            return [['The gradient could not be reset. Please try again.'], []];
        }

        $input  = (array) $this->request->getPost('gradients');
        $errors = [];
        $posted = [];
        $values = [];

        foreach ($cards as $key => $card) {
            $row = isset($input[$key]) && is_array($input[$key]) ? $input[$key] : [];

            foreach (['start' => 'Start color', 'end' => 'End color'] as $stop => $label) {
                $value               = trim((string) ($row[$stop] ?? ''));
                $posted[$key][$stop] = $value;
                if (preg_match(SitepanelTheme::GRADIENT_COLOR, $value)) {
                    $values[$prefix . $key . '-' . $stop] = SitepanelTheme::long_hex($value);
                } else {
                    $errors[] = $card['label'] . ' — ' . $label . ': enter a hex color, e.g. ' . $card[$stop] . '.';
                }
            }

            $angle                = trim((string) ($row['angle'] ?? ''));
            $posted[$key]['angle'] = $angle;
            if (ctype_digit($angle) && isset(SitepanelTheme::GRADIENT_ANGLES[(int) $angle])) {
                $values[$prefix . $key . '-angle'] = (string) (int) $angle;
            } else {
                $errors[] = $card['label'] . ' — Direction: choose one of the listed directions.';
            }
        }

        if (! $errors) {
            if ($this->settings_model->save($values, $this->admin['admin_id'])) {
                $this->site_settings->refresh();
                $this->session->setFlashdata('sitepanel_success', 'Dashboard card gradients saved.');
                redirect_now($this->base);
            }
            $errors[] = 'The dashboard card gradients could not be saved. Please try again.';
        }

        return [$errors, $posted];
    }

    /**
     * Gradient form fields per card: saved values (or the values as entered
     * after an error), picker values, defaults and the text colour/contrast.
     *
     * @param array $posted key => [start, end, angle] as entered
     */
    protected function gradient_fields(array $posted): array
    {
        $defaults = config(SitepanelConfig::class)->themeStatGradients;
        $fields   = [];

        foreach ($this->sitepanel_theme->gradients() as $key => $saved) {
            $f = [
                'label'   => $saved['label'],
                'start'   => $posted[$key]['start'] ?? $saved['start'],
                'end'     => $posted[$key]['end'] ?? $saved['end'],
                'angle'   => $posted[$key]['angle'] ?? (string) $saved['angle'],
                'default' => $defaults[$key],
            ];
            $f['start_picker'] = $this->picker_value($f['start'], $saved['start']);
            $f['end_picker']   = $this->picker_value($f['end'], $saved['end']);
            [$f['text'], $f['contrast']] = SitepanelTheme::text_for($f['start_picker'], $f['end_picker']);

            $fields[$key] = $f;
        }

        return $fields;
    }

    /**
     * A colour as <input type="color"> accepts it (#rrggbb): #rgb is expanded
     * and #rrggbbaa loses its alpha; anything invalid shows $fallback.
     *
     * @param string $fallback Valid saved colour
     */
    protected function picker_value(string $color, string $fallback): string
    {
        if (! preg_match(SitepanelTheme::HEX_COLOR, $color)) {
            $color = $fallback;
        }
        $color = strtolower($color);
        if (strlen($color) === 4) {
            $color = '#' . $color[1] . $color[1] . $color[2] . $color[2] . $color[3] . $color[3];
        }

        return substr($color, 0, 7);
    }
}
