<?php
use Faker\Generator as Faker;
use Tests\Fixtures\Models\User;

$factory->define(User::class, function (Faker $faker) {
    return [
      'email'    => $faker->email
    ];
});
