<?php

namespace App\Exceptions;

use Exception;

class CategoryNotFoundErrorException extends Exception
{
	/**
	 * Report exception log
	 *
	 * @return void
	 */
	public function report()
	{
		\Log::debug('Category not found');
	}
}
