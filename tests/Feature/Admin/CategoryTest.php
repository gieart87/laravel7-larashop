<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

use App\Models\User;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Category;

class CategoryTest extends TestCase
{
	protected $admin;
	protected $operator;
	protected $user;

	/**
	 * Setup every thing before running the tests
	 *
	 * @return void
	 */
	public function setUp() : void
	{
		parent::setUp();

		$this->_setupPermissions();

		$this->admin = factory(User::class)->create();
		$this->admin->assignRole('admin');

		$this->operator = factory(User::class)->create();
		$this->operator->assignRole('operator');

		$this->user = factory(User::class)->create();
	}

	/**
	 * Setup the permissions
	 *
	 * @return void
	 */
	private function _setupPermissions()
	{
		$permissions = [
			'view_categories',
			'add_categories',
			'edit_categories',
			'delete_categories',
		];
		
		foreach ($permissions as $permission) {
			Permission::findOrCreate($permission);
		}

		Role::findOrCreate('admin')
			->givePermissionTo($permissions);

		Role::findOrCreate('operator')
			->givePermissionTo(['view_categories']);

		$this->app->make(\Spatie\Permission\PermissionRegistrar::class)->registerPermissions();
	}

	/**
	 * Setup the categories
	 *
	 * @return void
	 */
	private function _setupCategories()
	{
		factory(Category::class)->create(
			[
				'name' => 'Category one',
				'slug' => 'category-one',
			]
		);

		factory(Category::class)->create(
			[
				'name' => 'Category two',
				'slug' => 'category-two',
			]
		);
	}

	/**
	 * Test admin can view the category index
	 *
	 * @return void
	 */
	public function testAdminCanViewTheCategoryIndex()
	{
		$this->_setupCategories();

		$response = $this
			->actingAs($this->admin)
			->get('/admin/categories');
		
		$response->assertStatus(200);
		$response->assertSee('Category one');
		$response->assertSee('Category two');
	}

	/**
	 * Test admin can add a category
	 *
	 * @return void
	 */
	public function testAdminCanAddACategory()
	{
		$params = [
			'name' => $this->faker->words(2, true),
		];

		$response = $this
			->actingAs($this->admin)
			->post('/admin/categories', $params);

		$response->assertStatus(302);

		$category = Category::first();
		$this->assertEquals($params['name'], $category->name);
		$this->assertEquals(0, $category->parent_id);

		$response->assertRedirect('/admin/categories');
		$response->assertSessionHas('success', 'Category has been saved.');
	}

	/**
	 * Test admin can add a category with parent
	 *
	 * @return void
	 */
	public function testAdminCanAddACategoryWithParent()
	{
		$parentCategory = factory(Category::class)->create();

		$params = [
			'name' => $this->faker->words(2, true),
			'parent_id' => $parentCategory->id,
		];

		$response = $this
			->actingAs($this->admin)
			->post('/admin/categories', $params);

		$response->assertStatus(302);

		$category = Category::where('id', '!=', $parentCategory->id)
			->first();
		$this->assertEquals($params['name'], $category->name);
		$this->assertEquals($parentCategory->id, $category->parent_id);
		
		$response->assertRedirect('/admin/categories');
		$response->assertSessionHas('success', 'Category has been saved.');
	}

	/**
	 * Test admin can update a category
	 *
	 * @return void
	 */
	public function testAdminCanUpdateACategory()
	{
		$existCategory = factory(Category::class)->create();

		$params = [
			'name' => 'New category name',
		];

		$response = $this
			->actingAs($this->admin)
			->put('/admin/categories/'. $existCategory->id, $params);
		
		$response->assertStatus(302);

		$updatedCategory = Category::find($existCategory->id);
		$this->assertEquals($params['name'], $updatedCategory->name);
		$this->assertEquals($existCategory->parent_id, $updatedCategory->parent_id);

		$response->assertRedirect('/admin/categories');
		$response->assertSessionHas('success', 'Category has been updated.');
	}

	/**
	 * Test admin can delete a category
	 *
	 * @return void
	 */
	public function testAdminCanDeleteACategory()
	{
		$existCategory = factory(Category::class)->create();

		$response = $this
			->actingAs($this->admin)
			->delete('/admin/categories/'. $existCategory->id);

		$response->assertStatus(302);

		$categories = Category::all();
		$this->assertCount(0, $categories);

		$response->assertRedirect('/admin/categories');
		$response->assertSessionHas('success', 'Category has been deleted.');
	}

	/**
	 * Test operator can view the category index
	 *
	 * @return void
	 */
	public function testOperatorCanViewTheCategoryIndex()
	{
		$this->_setupCategories();

		$response = $this
			->actingAs($this->operator)
			->get('/admin/categories');
		
		$response->assertStatus(200);
		$response->assertSee('Category one');
		$response->assertSee('Category two');
	}


	// ========= Negative ===================================

	/**
	 * Test admin can not add a categor with blank name
	 *
	 * @return void
	 */
	public function testAdminCanNotAddACategoryWithBlankName()
	{
		$params = [];

		$response = $this
			->actingAs($this->admin)
			->post('/admin/categories', $params);

		$response->assertStatus(302);

		$categories = Category::all();
		$this->assertCount(0, $categories);

		$errors = session('errors');
		
		$response->assertSessionHasErrors();
		$this->assertEquals('The name field is required.', $errors->get('name')[0]);
	}

	/**
	 * Test admin can not add a duplicated category
	 *
	 * @return void
	 */
	public function testAdminCanNotAddADuplicatedCategory()
	{
		$existCategory = factory(Category::class)->create();

		$params = [
			'name' => $existCategory->name,
		];

		$response = $this
			->actingAs($this->admin)
			->post('/admin/categories', $params);

		$response->assertStatus(302);

		$categories = Category::all();
		$this->assertCount(1, $categories);

		$errors = session('errors');
		
		$response->assertSessionHasErrors();
		$this->assertEquals('The name has already been taken.', $errors->get('name')[0]);
	}

	/**
	 * Test operator can not add a category
	 *
	 * @return void
	 */
	public function testOperatorCanNotAddACategory()
	{
		$params = [
			'name' => $this->faker->words(2, true),
		];

		$response = $this
			->actingAs($this->operator)
			->post('/admin/categories', $params);
			
		$response->assertStatus(403);
	}

	/**
	 * Test operator can not update a category
	 *
	 * @return void
	 */
	public function testOperatorCanNotUpdateACategory()
	{
		$existCategory = factory(Category::class)->create();

		$params = [
			'name' => 'test update',
		];

		$response = $this
			->actingAs($this->operator)
			->put('/admin/categories/'. $existCategory->id, $params);

		$response->assertStatus(403);
	}

	/**
	 * Test operator can not delete a category
	 *
	 * @return void
	 */
	public function testOperatorCanNotDeleteACategory()
	{
		$existCategory = factory(Category::class)->create();

		$response = $this
			->actingAs($this->operator)
			->delete('/admin/categories/'. $existCategory->id);

		$response->assertStatus(403);
	}

	/**
	 * Test user can not view the category index
	 *
	 * @return void
	 */
	public function testUserCanNotViewTheCategoryIndex()
	{
		$this->_setupCategories();

		$response = $this
			->actingAs($this->user)
			->get('/admin/categories');
			
		$response->assertStatus(403);
	}

	/**
	 * Test user can not add a category
	 *
	 * @return void
	 */
	public function testUserCanNotAddACategory()
	{
		$params = [
			'name' => $this->faker->words(2, true),
		];

		$response = $this
			->actingAs($this->user)
			->post('/admin/categories', $params);

		$response->assertStatus(403);
	}

	/**
	 * Test user can not update a category
	 *
	 * @return void
	 */
	public function testUserCanNotUpdateACategory()
	{
		$existCategory = factory(Category::class)->create();

		$params = [
			'name' => 'Test update',
		];

		$response = $this
			->actingAs($this->user)
			->put('/admin/categories/'. $existCategory->id, $params);

		$response->assertStatus(403);
	}

	/**
	 * Test user can not delete a category
	 *
	 * @return void
	 */
	public function testUserCanNotDeleteACategory()
	{
		$existCategory = factory(Category::class)->create();

		$response = $this
			->actingAs($this->user)
			->delete('/admin/categories/'. $existCategory->id);

		$response->assertStatus(403);
	}
}
