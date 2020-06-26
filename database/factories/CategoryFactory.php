<?php

use App\Models\Category;
use Faker\Generator as Faker;

$factory->define(
	Category::class,
	function (Faker $faker) {
		$name = $this->faker->words(2, true);
		return [
			'name' => $name,
			'slug' => Str::slug($name),
			'parent_id' => 0,
		];
	}
);
