<?php

namespace App\Repositories\Front\Interfaces;

interface CatalogueRepositoryInterface
{
    public function paginate($perPage, $request);

    public function findBySlug($slug);

    public function findBySKU($sku);

    public function findProductByID($productID);

    public function getAttributeOptions($product, $attributeName);

    public function getParentCategories();

    public function getAttributeFilters($attributeCode);

    public function getMinPrice();

    public function getMaxPrice();

    public function getProductByAttributes($product, $params);

    public function checkProductInventory($product, $qtyRequested);
}
