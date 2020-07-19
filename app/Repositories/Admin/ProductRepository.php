<?php

namespace App\Repositories\Admin;

use App\Models\Product;
use App\Models\Category;
use App\Models\ProductImage;
use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\ProductAttributeValue;
use App\Models\ProductInventory;

use Str;
use DB;

use App\Repositories\Admin\Interfaces\ProductRepositoryInterface;
use App\Repositories\Admin\Interfaces\AttributeRepositoryInterface;

class ProductRepository implements ProductRepositoryInterface
{
    protected $attribteRepository;

    /**
     * Create a new controller instance.
     *
     * @param AttributeRepositoryInterface $attributeRepository AttributeRepository
     *
     * @return void
     */
    public function __construct(AttributeRepositoryInterface $attributeRepository)
    {
        $this->_attributeRepository = $attributeRepository;
    }

    /**
     * Paginated collection
     *
     * @param int $perPage per page items
     *
     * @return Collection
     */
    public function paginate($perPage)
    {
        return Product::orderBy('name', 'ASC')->paginate($perPage);
    }

    /**
     * Create a product
     *
     * @param array $params params
     *
     * @return void
     */
    public function create($params)
    {
        $params['slug'] = Str::slug($params['name']);

        $product = DB::transaction(
            function () use ($params) {
                $categoryIds = !empty($params['category_ids']) ? $params['category_ids'] : [];
                $product = Product::create($params);
                $product->categories()->sync($categoryIds);

                if ($params['type'] == 'configurable') {
                    $this->generateProductVariants($product, $params);
                }

                return $product;
            }
        );
        
        return $product;
    }

    /**
     * Find single product by id
     *
     * @param int $id product id
     *
     * @return Product
     */
    public function findById($id)
    {
        return Product::findOrFail($id);
    }

    /**
     * Update a product
     *
     * @param array $params params
     * @param int   $id     product id
     *
     * @return void
     */
    public function update($params, int $id)
    {
        $params['slug'] = Str::slug($params['name']);
        $product = Product::findOrFail($id);

        $saved = false;
        $saved = DB::transaction(
            function () use ($product, $params) {
                $categoryIds = !empty($params['category_ids']) ? $params['category_ids'] : [];
                $product->update($params);
                $product->categories()->sync($categoryIds);

                if ($product->type == 'configurable') {
                    $this->updateProductVariants($params);
                } else {
                    ProductInventory::updateOrCreate(['product_id' => $product->id], ['qty' => $params['qty']]);
                }

                return true;
            }
        );
        
        return $saved;
    }

    /**
     * Delete a product
     *
     * @param int $id product id
     *
     * @return boolean
     */
    public function delete($id)
    {
        $product  = Product::findOrFail($id);
        if ($product->variants) {
            foreach ($product->variants as $variant) {
                $variant->delete();
            }
        }
        return $product->delete();
    }

    /**
     * Add an image to product
     *
     * @param int  $id    product id
     * @param file $image image file
     *
     * @return void
     */
    public function addImage($id, $image)
    {
        $product = Product::findOrFail($id);

        $name = $product->slug . '_' . time();
        $fileName = $name . '.' . $image->getClientOriginalExtension();

        $folder = ProductImage::UPLOAD_DIR. '/images';

        $filePath = $image->storeAs($folder . '/original', $fileName, 'public');

        $resizedImage = $this->resizeImage($image, $fileName, $folder);

        $params = array_merge(
            [
                'product_id' => $product->id,
                'path' => $filePath,
            ],
            $resizedImage
        );

        return ProductImage::create($params);
    }

    /**
     * Get product statuses
     *
     * @return array
     */
    public function statuses()
    {
        return Product::statuses();
    }

    /**
     * Get product types
     *
     * @return array
     */
    public function types()
    {
        return Product::types();
    }

    /**
     * Find image by id
     *
     * @param int $id image id
     *
     * @return Image
     */
    public function findImageById($id)
    {
        return ProductImage::findOrFail($id);
    }

    /**
     * Remove image by id
     *
     * @param int $id image id
     *
     * @return void
     */
    public function removeImage($id)
    {
        $image = $this->findImageById($id);

        return $image->delete();
    }
    /**
     * Generate product variants for the configurable product
     *
     * @param Product $product product object
     * @param array   $params  params
     *
     * @return void
     */
    private function generateProductVariants($product, $params)
    {
        $configurableAttributes = $this->_attributeRepository->getConfigurableAttributes();

        $variantAttributes = [];
        foreach ($configurableAttributes as $attribute) {
            $variantAttributes[$attribute->code] = $params[$attribute->code];
        }

        $variants = $this->generateAttributeCombinations($variantAttributes);
        
        if ($variants) {
            foreach ($variants as $variant) {
                $variantParams = [
                    'parent_id' => $product->id,
                    'user_id' => $params['user_id'],
                    'sku' => $product->sku . '-' .implode('-', array_values($variant)),
                    'type' => 'simple',
                    'name' => $product->name . $this->convertVariantAsName($variant),
                ];

                $variantParams['slug'] = Str::slug($variantParams['name']);

                $newProductVariant = Product::create($variantParams);

                $categoryIds = !empty($params['category_ids']) ? $params['category_ids'] : [];
                $newProductVariant->categories()->sync($categoryIds);

                $this->saveProductAttributeValues($newProductVariant, $variant, $product->id);
            }
        }
    }
    
    /**
     * Generate attribute combination depend on the provided attributes
     *
     * @param array $arrays attributes
     *
     * @return array
     */
    private function generateAttributeCombinations($arrays)
    {
        $result = [[]];
        foreach ($arrays as $property => $property_values) {
            $tmp = [];
            foreach ($result as $result_item) {
                foreach ($property_values as $property_value) {
                    $tmp[] = array_merge($result_item, array($property => $property_value));
                }
            }
            $result = $tmp;
        }
        return $result;
    }
    
    /**
     * Convert variant attributes as variant name
     *
     * @param array $variant variant
     *
     * @return string
     */
    private function convertVariantAsName($variant)
    {
        $variantName = '';
        
        foreach (array_keys($variant) as $key => $code) {
            $attributeOptionID = $variant[$code];
            $attributeOption = AttributeOption::find($attributeOptionID);
            
            if ($attributeOption) {
                $variantName .= ' - ' . $attributeOption->name;
            }
        }

        return $variantName;
    }
    
    /**
     * Save the product attribute values
     *
     * @param Product $product         product object
     * @param array   $variant         variant
     * @param int     $parentProductID parent product ID
     *
     * @return void
     */
    private function saveProductAttributeValues($product, $variant, $parentProductID)
    {
        foreach (array_values($variant) as $attributeOptionID) {
            $attributeOption = AttributeOption::find($attributeOptionID);
           
            $attributeValueParams = [
                'parent_product_id' => $parentProductID,
                'product_id' => $product->id,
                'attribute_id' => $attributeOption->attribute_id,
                'text_value' => $attributeOption->name,
            ];

            ProductAttributeValue::create($attributeValueParams);
        }
    }
    
    /**
     * Product variants
     *
     * @param array $params params
     *
     * @return void
     */
    private function updateProductVariants($params)
    {
        if ($params['variants']) {
            foreach ($params['variants'] as $productParams) {
                $product = Product::find($productParams['id']);
                $product->update($productParams);

                $product->status = $params['status'];
                $product->save();
                
                ProductInventory::updateOrCreate(['product_id' => $product->id], ['qty' => $productParams['qty']]);
            }
        }
    }
    

    /**
     * Resize image
     *
     * @param file   $image    raw file
     * @param string $fileName image file name
     * @param string $folder   folder name
     *
     * @return Response
     */
    private function resizeImage($image, $fileName, $folder)
    {
        $resizedImage = [];

        $smallImageFilePath = $folder . '/small/' . $fileName;
        $size = explode('x', ProductImage::SMALL);
        list($width, $height) = $size;

        $smallImageFile = \Image::make($image)->fit($width, $height)->stream();
        if (\Storage::put('public/' . $smallImageFilePath, $smallImageFile)) {
            $resizedImage['small'] = $smallImageFilePath;
        }
        
        $mediumImageFilePath = $folder . '/medium/' . $fileName;
        $size = explode('x', ProductImage::MEDIUM);
        list($width, $height) = $size;

        $mediumImageFile = \Image::make($image)->fit($width, $height)->stream();
        if (\Storage::put('public/' . $mediumImageFilePath, $mediumImageFile)) {
            $resizedImage['medium'] = $mediumImageFilePath;
        }

        $largeImageFilePath = $folder . '/large/' . $fileName;
        $size = explode('x', ProductImage::LARGE);
        list($width, $height) = $size;

        $largeImageFile = \Image::make($image)->fit($width, $height)->stream();
        if (\Storage::put('public/' . $largeImageFilePath, $largeImageFile)) {
            $resizedImage['large'] = $largeImageFilePath;
        }

        $extraLargeImageFilePath  = $folder . '/xlarge/' . $fileName;
        $size = explode('x', ProductImage::EXTRA_LARGE);
        list($width, $height) = $size;

        $extraLargeImageFile = \Image::make($image)->fit($width, $height)->stream();
        if (\Storage::put('public/' . $extraLargeImageFilePath, $extraLargeImageFile)) {
            $resizedImage['extra_large'] = $extraLargeImageFilePath;
        }

        return $resizedImage;
    }
}
