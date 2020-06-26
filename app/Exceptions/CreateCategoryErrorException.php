<?php

namespace App\Exceptions;

use Exception;

class CreateCategoryErrorException extends Exception
{
	/**
	 * Report exception log
	 *
	 * @return void
	 */
	public function report()
	{
		\Log::debug('Error on creating a category');
	}
}
