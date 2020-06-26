<?php

namespace Tests\Unit\Admin;

use Tests\TestCase;

use Illuminate\Support\Str;

use App\Repositories\Admin\CategoryRepository;
use App\Models\Category;

use App\Exceptions\CreateCategoryErrorException;
use App\Exceptions\UpdateCategoryErrorException;
use App\Exceptions\CategoryNotFoundErrorException;

class CategoryRepositoryTest extends TestCase
{
	/**
	 * Test can create a category
	 *
	 * @return void
	 */
	public function testCanCreateACategory()
	{
		$params = [
			'name' => $this->faker->words(2, true),
		];

		$categoryRepository = new CategoryRepository(new Category);
		$category = $categoryRepository->create($params);

		$this->assertInstanceOf(Category::class, $category);
		$this->assertEquals($params['name'], $category->name);
		$this->assertEquals(Str::slug($params['name']), $category->slug);
		$this->assertEquals(0, $category->parent_id);
	}

	/**
	 * Test can create a category with parent
	 *
	 * @return void
	 */
	public function testCanCreateACategoryWithParent()
	{
		$name = $this->faker->words(2, true);
		$parentCategory = Category::create(
			[
				'name' => $name,
				'slug' => Str::slug($name),
				'parent_id' => 0,
			]
		);

		$params = [
			'name' => $this->faker->words(2, true),
			'parent_id' => $parentCategory->id,
		];

		$categoryRepository = new CategoryRepository(new Category);
		$category = $categoryRepository->create($params);

		$this->assertInstanceOf(Category::class, $category);
		$this->assertEquals($params['name'], $category->name);
		$this->assertEquals(Str::slug($params['name']), $category->slug);
		$this->assertEquals($parentCategory->id, $category->parent_id);
	}

	/**
	 * Can display a paginated categories
	 *
	 * @return void
	 */
	public function testCanDisplayAPaginatedCategories()
	{
		$categories = factory(Category::class, 10)->create();

		$this->assertCount(10, Category::get());

		$categoryRepository = new CategoryRepository(new Category);
		$paginatedCategories = $categoryRepository->paginate(5);

		$this->assertCount(5, $paginatedCategories);
	}

	/**
	 * Can find category by id
	 *
	 * @return void
	 */
	public function testCanFindCategoryById()
	{
		$newCategory = factory(Category::class)->create();

		$categoryRepository = new CategoryRepository(new Category);
		$category = $categoryRepository->findById($newCategory->id);

		$this->assertInstanceOf(Category::class, $category);
		$this->assertEquals($newCategory->name, $category->name);
		$this->assertEquals($newCategory->slug, $category->slug);
		$this->assertEquals($newCategory->parent_id, $category->parent_id);
	}

	/**
	 * Can get categories as a dropdown
	 *
	 * @return void
	 */
	public function testCanGetCategoriesAsADropdown()
	{
		factory(Category::class, 3)->create();

		$categories = Category::orderBy('name', 'asc')->get();

		$categoryRepository = new CategoryRepository(new Category);
		$categoryDropdown = $categoryRepository->getCategoryDropdown();

		$this->assertEquals($categories, $categoryDropdown);
	}

	/**
	 * Can get categories as a dropdown with exception for a certain category
	 *
	 * @return void
	 */
	public function testCanGetCategoriesAsADropdownWithExceptionForACertainCategory()
	{
		factory(Category::class, 3)->create();

		$firstCategory = Category::first();

		$categoryRepository = new CategoryRepository(new Category);
		$categoryDropdown = $categoryRepository->getCategoryDropdown($firstCategory->id);

		$categories = Category::where('id', '!=', $firstCategory->id)
			->orderBy('name', 'asc')->get();

		$this->assertEquals($categories, $categoryDropdown);
	}

	/**
	 * Can update a category
	 *
	 * @return void
	 */
	public function testCanUpdateACategory()
	{
		$existCategory = Category::create(
			[
				'name' => 'Old Name',
				'slug' => 'old-name',
				'parent_id' => 0,
			]
		);

		$params = [
			'name' => 'New Name',
		];

		$categoryRepository = new CategoryRepository(new Category);
		$categoryRepository->update($params, $existCategory->id);

		$updatedCategory = Category::find($existCategory->id);

		$this->assertEquals($existCategory->id, $updatedCategory->id);
		$this->assertEquals('New Name', $updatedCategory->name);
		$this->assertEquals('new-name', $updatedCategory->slug);
	}

	/**
	 * Can delete a category
	 *
	 * @return void
	 */
	public function testCanDeleteACategory()
	{
		$category = factory(Category::class)->create();

		$this->assertCount(1, Category::get());

		$categoryRepository = new CategoryRepository(new Category);
		$categoryRepository->delete($category->id);

		$this->assertCount(0, Category::get());
	}

	/**
	 * Should throw an error when create a category and the required fields are not filled
	 *
	 * @return void
	 */
	public function testShouldThrowAnErrorWhenCreateACategoryAndTheRequiredFieldsAreNotFilled()
	{
		$this->expectException(CreateCategoryErrorException::class);

		$params = [];
		$categoryRepository = new CategoryRepository(new Category);
		$categoryRepository->create($params);
	}

	/**
	 * Should throw an error when update a category and the require fields are not filled
	 *
	 * @return void
	 */
	public function testShouldThrowAnErrorWhenUpdateACategoryAndTheRequireFieldsAreNotFilled()
	{
		$this->expectException(UpdateCategoryErrorException::class);

		$category = factory(Category::class)->create();

		$params = [];
		$categoryRepository = new CategoryRepository(new Category);
		$categoryRepository->update($params, $category->id);
	}

	/**
	 * Should throw an error when getting category by invalid id
	 *
	 * @return void
	 */
	public function testShouldThrowAnErrorWhenGettingCategoryByInvalidId()
	{
		$this->expectException(CategoryNotFoundErrorException::class);

		$category = factory(Category::class)->create();

		$categoryRepository = new CategoryRepository(new Category);
		$categoryRepository->findById(123);
	}
}
