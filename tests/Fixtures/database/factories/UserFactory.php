<?php
use Faker\Generator as Faker;
use Tests\Fixtures\Models\TestUser;

$factory->define(TestUser::class, function (Faker $faker) {
    return [
      'email'    => $faker->email
    ];
});
