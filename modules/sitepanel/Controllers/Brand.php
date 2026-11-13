<?php

namespace Modules\Sitepanel\Controllers;

use Modules\Lookup\Models\LookupModel;
use Modules\Brand\Models\BrandModel;

/**
 * Sitepanel > Manage Entities > Manage Brands (wl_brands). Name (unique, at most 100 characters) and an optional logo (uploads/brand).
 * Actions and rules: StoreLookup.
 */
class Brand extends StoreLookup
{
    protected const KIND         = 'brand';
    protected const NAME_MAX     = 100;
    protected const IMAGE_FOLDER = 'brand';

    protected string $base           = 'sitepanel/brand';
    protected string $item           = 'Brand';
    protected string $plural         = 'brands';
    protected string $menu_highlight = 'brand';

    protected function make_model(): LookupModel
    {
        return new BrandModel();
    }
}
