<?php

namespace App\Repositories\Admin\Interfaces;

interface AttributeRepositoryInterface
{
    /**
     * Get configurable attribuets
     *
     * @return Collection
     */
    public function getConfigurableAttributes();
}
