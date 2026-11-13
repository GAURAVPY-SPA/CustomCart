<?php

namespace Modules\Sitepanel\Controllers;

use Modules\Lookup\Models\LookupModel;
use Modules\Color\Models\ColorModel;

/**
 * Sitepanel > Manage Entities > Manage Colors (wl_colors). Name (unique, at most 32 characters) and swatch colour (#rrggbb).
 * Actions and rules: StoreLookup.
 */
class Color extends StoreLookup
{
    protected const KIND         = 'color';
    protected const NAME_MAX     = 32;
    protected const IMAGE_FOLDER = '';

    protected string $base           = 'sitepanel/color';
    protected string $item           = 'Colour';
    protected string $plural         = 'colours';
    protected string $menu_highlight = 'color';

    protected function make_model(): LookupModel
    {
        return new ColorModel();
    }
}
