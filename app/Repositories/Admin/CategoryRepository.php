<?php

namespace App\Repositories\Admin;

use App\Http\Requests\CategoryRequest;

use App\Repositories\Admin\Interfaces\CategoryRepositoryInterface;

use App\Models\Category;

class CategoryRepository implements CategoryRepositoryInterface
{
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
		return Category::findOrFail($categoryId);
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
	 * @param CategoryRequest $request request params
	 *
	 * @return Category
	 */
	public function create(CategoryRequest $request)
	{
		$params = $request->except('_token');
		$params['slug'] = \Str::slug($params['name']);
		$params['parent_id'] = (int)$params['parent_id'];
		
		return Category::create($params);
	}

	/**
	 * Update existing record
	 *
	 * @param CategoryRequest $request request params
	 * @param int             $id      category id
	 *
	 * @return Category
	 */
	public function update(CategoryRequest $request, $id)
	{
		$params = $request->except('_token');
		$params['slug'] = \Str::slug($params['name']);
		$params['parent_id'] = (int)$params['parent_id'];

		$category = Category::findOrFail($id);

		return $category->update($params);
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
