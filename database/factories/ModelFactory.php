<?php
use CoasterCms\Models\Block;
use CoasterCms\Models\BlockRepeater;
use CoasterCms\Models\Page;
use CoasterCms\Models\PageBlockRepeaterData;
use CoasterCms\Models\PageBlockRepeaterRows;
use CoasterCms\Models\PageLang;
use CoasterCms\Models\Template;
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

$factory->state(UserRole::class, 'superadmin', function () {
    return [
        'name' => 'Super Admin Role',
        'description' => 'Super Admin Role description',
        'admin' => 2,
    ];
});


$factory->define(Block::class, function () {
    return [
        'category_id' => 1,
        'name' => 'a_block',
        'label' => 'A block',
        'type' => 'string',
    ];
});


$factory->define(PageLang::class, function(Faker\Generator $faker) {
    $name = $faker->name;
    $page = factory(Page::class)->create();
    return [
        'page_id' => $page->id,
        'language_id' => 1,
        'live_version' => 1,
        'name' => $name,
        'url' => str_slug($name),
    ];
});

$factory->define(Page::class, function () {
    return [
        'live' => 1,
    ];
});

$factory->define(Template::class, function () {
    return [
        'label' => 'template',
        'template' => 'template',
    ];
});

$factory->define(Block::class, function () {
    return [
        'name' => 'black_name',
        'label' => 'Block label',
        'type' => 'string',
        'category_id' => 1,
    ];
});

$factory->define(BlockRepeater::class, function () {
    return [
      'block_id' => 2,
      'blocks' => '1',
    ];
});

$factory->define(PageBlockRepeaterRows::class, function () {
    return [
      'repeater_id' => 2,
      'row_id' => 1,
    ];
});

$factory->define(PageBlockRepeaterData::class, function () {
    return [
      'row_key' => 1,
      'block_id' => 1,
      'version' => 1,
      'content' => 'content',
    ];
});
