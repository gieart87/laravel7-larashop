<?php

namespace App\Repositories\Admin;

use Illuminate\Support\Str;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use App\Repositories\Admin\Interfaces\CategoryRepositoryInterface;

use App\Models\Category;

use App\Exceptions\CreateCategoryErrorException;
use App\Exceptions\UpdateCategoryErrorException;
use App\Exceptions\CategoryNotFoundErrorException;

class CategoryRepository implements CategoryRepositoryInterface
{
	private $_model;

	/**
	 * Create a new controller instance.
	 *
	 * @param Category $model category object
	 *
	 * @return void
	 */
	public function __construct(Category $model)
	{
		$this->_model = $model;
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
		return Category::orderBy('name', 'ASC')->paginate($perPage);
	}

	/**
	 * Find single record by id
	 *
	 * @param int $categoryId category id
	 *
	 * @return Category
	 */
	public function findById($categoryId)
	{
		try {
			return Category::findOrFail($categoryId);
		} catch (ModelNotFoundException $e) {
			throw new CategoryNotFoundErrorException('Category not found');
		}
	}

	/**
	 * Get categories as dropdown
	 *
	 * @param int $exceptCategoryId except category id
	 *
	 * @return array
	 */
	public function getCategoryDropdown($exceptCategoryId = null)
	{
		$categories = new Category;
		
		if ($exceptCategoryId) {
			$categories = $categories->where('id', '!=', $exceptCategoryId);
		}

		$categories = $categories->orderBy('name', 'asc');

		return $categories->get();
	}

	/**
	 * Create new record
	 *
	 * @param array $params request params
	 *
	 * @return Category
	 */
	public function create($params)
	{
		$params['slug'] = isset($params['name']) ? Str::slug($params['name']) : null;
		
		if (!isset($params['parent_id'])) {
			$params['parent_id'] = 0;
		}
		
		try {
			return $this->_model::create($params);
		} catch (QueryException $e) {
			throw new CreateCategoryErrorException('Error on creating a category');
		}
	}

	/**
	 * Update existing record
	 *
	 * @param array $params request params
	 * @param int   $id     category id
	 *
	 * @return Category
	 */
	public function update($params, $id)
	{
		$params['slug'] = isset($params['name']) ? Str::slug($params['name']) : null;
		
		if (!isset($params['parent_id'])) {
			$params['parent_id'] = 0;
		}

		$category = Category::findOrFail($id);

		try {
			return $category->update($params);
		} catch (QueryException $e) {
			throw new UpdateCategoryErrorException('Error on updating a category');
		}
	}

	/**
	 * Delete a record
	 *
	 * @param int $categoryId category id
	 *
	 * @return boolean
	 */
	public function delete($categoryId)
	{
		$category  = Category::findOrFail($categoryId);

		return $category->delete();
	}
}
