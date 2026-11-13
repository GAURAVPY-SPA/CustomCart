<?php

namespace Modules\Material\Models;

use Modules\Lookup\Models\LookupModel;

/**
 * Materials (wl_materials). A product has one material (wl_products.material_id).
 */
class MaterialModel extends LookupModel
{
    public const TABLE        = 'wl_materials';
    public const PRIMARY_KEY  = 'material_id';
    public const NAME_FIELD   = 'material_name';
    public const ADDED_FIELD  = 'material_date_added';
    public const UPDATE_FIELD = 'material_date_updated';
    public const USAGE        = ['wl_products', 'material_id'];
}
