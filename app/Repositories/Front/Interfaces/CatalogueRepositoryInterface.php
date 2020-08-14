<?php

namespace App\Repositories\Front\Interfaces;

interface CatalogueRepositoryInterface
{
    public function paginate($perPage, $request);

    public function findBySlug($slug);

    public function getAttributeOptions($product, $attributeName);

    public function getParentCategories();

    public function getAttributeFilters($attributeCode);

    public function getMinPrice();

    public function getMaxPrice();
}
