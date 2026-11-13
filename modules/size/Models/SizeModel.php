<?php

namespace Modules\Size\Models;

use Modules\Lookup\Models\LookupModel;

/**
 * Product sizes (wl_sizes). A product's sizes are in wl_product_sizes.
 */
class SizeModel extends LookupModel
{
    public const TABLE        = 'wl_sizes';
    public const PRIMARY_KEY  = 'size_id';
    public const NAME_FIELD   = 'size_name';
    public const ADDED_FIELD  = 'size_date_added';
    public const UPDATE_FIELD = 'size_date_updated';
    public const USAGE        = ['wl_product_sizes', 'size_id'];
}
