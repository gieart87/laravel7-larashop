<?php

namespace App\Repositories\Admin;

use App\Models\Attribute;
use App\Models\AttributeOption;

use App\Repositories\Admin\Interfaces\AttributeRepositoryInterface;

class AttributeRepository implements AttributeRepositoryInterface
{

    /**
     * Get configurable attributes
     *
     * @return Collection
     */
    public function getConfigurableAttributes()
    {
        return Attribute::where('is_configurable', true)->get();
    }
}
