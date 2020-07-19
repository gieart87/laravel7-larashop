<?php

use App\Models\Product;
use App\Models\User;

use Faker\Generator as Faker;

$factory->define(
	Product::class,
	function (Faker $faker) {
		$user = factory(User::class)->create();
		$name = $faker->words(2, true);

		return [
			'user_id' => $user->id,
			'sku' => $faker->isbn10,
			'type' => 'simple',
			'name' => $name,
			'slug' => Str::slug($name),
			'price' => $faker->randomFloat,
			'weight' => $faker->randomNumber,
			'status' => Product::ACTIVE,
		];
	}
);
