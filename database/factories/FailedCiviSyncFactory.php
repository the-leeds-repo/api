<?php

use App\Models\FailedCiviSync;
use App\Models\Organisation;
use Faker\Generator as Faker;

$factory->define(FailedCiviSync::class, function (Faker $faker) {
    return [
        'organisation_id' => function () {
            return factory(Organisation::class)->create()->id;
        },
        'status_code' => 422,
    ];
});
