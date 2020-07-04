<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use Session;

use App\Authorizable;

use App\Repositories\Admin\Interfaces\CategoryRepositoryInterface;

class CategoryController extends Controller
{
	use Authorizable;

	private $_categoryRepository;

	/**
	 * Create a new controller instance.
	 *
	 * @param CategoryRepositoryInterface $categoryRepository CategoryRepositoryInterface
	 *
	 * @return void
	 */
	public function __construct(CategoryRepositoryInterface $categoryRepository)
	{
		parent::__construct();

		$this->_categoryRepository = $categoryRepository;

		$this->data['currentAdminMenu'] = 'catalog';
		$this->data['currentAdminSubMenu'] = 'category';
	}
	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index()
	{
		$this->data['categories'] = $this->_categoryRepository->paginate(10);
		return view('admin.categories.index', $this->data);
	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function create()
	{
		$this->data['categories'] = $this->_categoryRepository->getCategoryDropdown();
		return view('admin.categories.form', $this->data);
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @param \Illuminate\Http\Request $request request params
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function store(CategoryRequest $request)
	{
		$params = $request->validated();

		if ($this->_categoryRepository->create($params)) {
			Session::flash('success', 'Category has been saved.');
		}
		return redirect('admin/categories');
	}

	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param int $id category id
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function edit($id)
	{
		$this->data['categories'] = $this->_categoryRepository->getCategoryDropdown($id);
		$this->data['category'] = $this->_categoryRepository->findById($id);

		return view('admin.categories.form', $this->data);
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param CategoryRequest $request request params
	 * @param int             $id      category id
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function update(CategoryRequest $request, $id)
	{
		$params = $request->validated();

		if ($this->_categoryRepository->update($params, $id)) {
			Session::flash('success', 'Category has been updated.');
		}

		return redirect('admin/categories');
	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param int $id category id
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function destroy($id)
	{
		if ($this->_categoryRepository->delete($id)) {
			Session::flash('success', 'Category has been deleted.');
		}

		return redirect('admin/categories');
	}
}
