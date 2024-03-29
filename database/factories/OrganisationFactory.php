<?php

use App\Models\Organisation;
use Faker\Generator as Faker;
use Illuminate\Support\Str;

$factory->define(Organisation::class, function (Faker $faker) {
    $name = $faker->unique()->company;

    return [
        'slug' => Str::slug($name) . '-' . mt_rand(1, 1000),
        'name' => $name,
        'description' => 'This organisation provides x service.',
        'url' => $faker->url,
        'email' => $faker->safeEmail,
        'phone' => random_uk_phone(),
        'is_hidden' => false,
        'civi_sync_enabled' => false,
    ];
});
