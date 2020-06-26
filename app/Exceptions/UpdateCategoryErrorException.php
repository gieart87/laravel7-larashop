<?php

namespace App\Exceptions;

use Exception;

class UpdateCategoryErrorException extends Exception
{
	/**
	 * Report exception log
	 *
	 * @return void
	 */
	public function report()
	{
		\Log::debug('Error on updating a category');
	}
}
