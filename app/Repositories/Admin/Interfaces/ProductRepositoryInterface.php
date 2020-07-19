<?php

namespace App\Repositories\Admin\Interfaces;

interface ProductRepositoryInterface
{
    /**
     * Paginated collection
     *
     * @param int $perPage per page items
     *
     * @return Collection
     */
    public function paginate(int $perPage);

    /**
     * Create a product
     *
     * @param array $params params
     *
     * @return void
     */
    public function create($params);

    /**
     * Find single product by id
     *
     * @param int $id product id
     *
     * @return Product
     */
    public function findById(int $id);

    /**
     * Update a product
     *
     * @param array $params params
     * @param int   $id     product id
     *
     * @return void
     */
    public function update($params, int $id);

    /**
     * Delete a product
     *
     * @param int $id product id
     *
     * @return boolean
     */
    public function delete(int $id);

    /**
     * Add an image to product
     *
     * @param int  $id    product id
     * @param file $image image file
     *
     * @return void
     */
    public function addImage(int $id, $image);

    /**
     * Find image by id
     *
     * @param int $id image id
     *
     * @return Image
     */
    public function findImageById(int $id);

    /**
     * Remove image by id
     *
     * @param int $id image id
     *
     * @return void
     */
    public function removeImage(int $id);

    /**
     * Get product types
     *
     * @return array
     */
    public function types();

    /**
     * Get product statuses
     *
     * @return array
     */
    public function statuses();
}
