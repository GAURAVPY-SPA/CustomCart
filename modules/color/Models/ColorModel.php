<?php

namespace Modules\Color\Models;

use Modules\Lookup\Models\LookupModel;

/**
 * Product colours (wl_colors). color_code is the swatch (#rrggbb); a product's colours are in wl_product_colors.
 */
class ColorModel extends LookupModel
{
    public const TABLE        = 'wl_colors';
    public const PRIMARY_KEY  = 'color_id';
    public const NAME_FIELD   = 'color_name';
    public const ADDED_FIELD  = 'color_date_added';
    public const UPDATE_FIELD = 'color_date_updated';
    public const USAGE        = ['wl_product_colors', 'color_id'];
}
