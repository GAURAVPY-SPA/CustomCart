<?php

namespace Modules\Sitepanel\Controllers;

use Modules\Lookup\Models\LookupModel;
use Modules\Material\Models\MaterialModel;

/**
 * Sitepanel > Manage Entities > Manage Materials (wl_materials). Name (unique, at most 100 characters).
 * Actions and rules: StoreLookup.
 */
class Material extends StoreLookup
{
    protected const KIND         = 'material';
    protected const NAME_MAX     = 100;
    protected const IMAGE_FOLDER = '';

    protected string $base           = 'sitepanel/material';
    protected string $item           = 'Material';
    protected string $plural         = 'materials';
    protected string $menu_highlight = 'material';

    protected function make_model(): LookupModel
    {
        return new MaterialModel();
    }
}
