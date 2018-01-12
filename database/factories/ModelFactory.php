<?php
use CoasterCms\Models\User;
use CoasterCms\Models\UserRole;
/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/

/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(User::class, function (Faker\Generator $faker) {
    static $password;

    return [
        'name' => $faker->name,
        'email' => $faker->unique()->safeEmail,
        'password' => $password ?: $password = bcrypt('secret'),
        'remember_token' => str_random(10),
    ];
});

$factory->define(UserRole::class, function () {
    return [
        'name' => 'Frontend Role',
        'description' => 'Frontend Role description',
        'admin' => 0,
    ];
});


$factory->state(UserRole::class, 'admin', function () {
    return [
        'name' => 'Admin Role',
        'description' => 'Admin Role description',
        'admin' => 1,
    ];
});
