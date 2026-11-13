<?php

namespace Modules\Brand\Models;

use Modules\Lookup\Models\LookupModel;

/**
 * Brands (wl_brands). Optional logo in uploads/brand; a product has one brand (wl_products.brand_id).
 */
class BrandModel extends LookupModel
{
    public const TABLE        = 'wl_brands';
    public const PRIMARY_KEY  = 'brand_id';
    public const NAME_FIELD   = 'brand_name';
    public const ADDED_FIELD  = 'brand_date_added';
    public const UPDATE_FIELD = 'brand_date_updated';
    public const USAGE        = ['wl_products', 'brand_id'];
}
