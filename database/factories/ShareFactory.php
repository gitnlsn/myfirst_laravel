<?php

use Faker\Generator as Faker;

$factory->define(App\Share::class, function (Faker $faker) {
    return [
        'share_name'    =>$faker->name(),
        'share_price'   =>rand(100,1000),
        'share_qty'     =>rand(  0,1000),
    ];
});
