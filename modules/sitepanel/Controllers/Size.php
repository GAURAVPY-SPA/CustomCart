<?php

namespace Modules\Sitepanel\Controllers;

use Modules\Lookup\Models\LookupModel;
use Modules\Size\Models\SizeModel;

/**
 * Sitepanel > Manage Entities > Manage Size (wl_sizes). Name (unique, at most 32 characters), e.g. S, M, L, XL.
 * Actions and rules: StoreLookup.
 */
class Size extends StoreLookup
{
    protected const KIND         = 'size';
    protected const NAME_MAX     = 32;
    protected const IMAGE_FOLDER = '';

    protected string $base           = 'sitepanel/size';
    protected string $item           = 'Size';
    protected string $plural         = 'sizes';
    protected string $menu_highlight = 'size';

    protected function make_model(): LookupModel
    {
        return new SizeModel();
    }
}
