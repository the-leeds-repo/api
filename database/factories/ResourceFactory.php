<?php

use App\Models\Organisation;
use App\Models\Resource;
use Faker\Generator as Faker;
use Illuminate\Support\Str;

/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(Resource::class, function (Faker $faker) {
    $name = $faker->unique()->company;

    return [
        'organisation_id' => function () {
            return factory(Organisation::class)->create()->id;
        },
        'slug' => Str::slug($name) . '-' . mt_rand(1, 1000),
        'name' => $name,
        'description' => 'This resource is for x.',
        'url' => $faker->url,
    ];
});
