<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateSettings extends Migration
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->create();
            $table->increments('id');
            $table->string('label');
            $table->string('name');
            $table->string('value');
            $table->integer('editable');
            $table->integer('hidden');
            $table->timestamps();
        });

        $date = new Carbon;

        DB::table('settings')->insert(
            array(
                array(
                    'label' => 'Site Name',
                    'name' => 'site.name',
                    'value' => 'Coaster CMS',
                    'editable' => 1,
                    'hidden' => 0,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'label' => 'Site Email',
                    'name' => 'site.email',
                    'value' => 'info@example.com',
                    'editable' => 1,
                    'hidden' => 0,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'label' => 'Site Page Limit',
                    'name' => 'site.pages',
                    'value' => '0',
                    'editable' => 1,
                    'hidden' => 0,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'label' => 'Admin Path',
                    'name' => 'admin.url',
                    'value' => 'admin',
                    'editable' => 1,
                    'hidden' => 0,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'label' => 'Admin Publishing',
                    'name' => 'admin.publishing',
                    'value' => '0',
                    'editable' => 1,
                    'hidden' => 0,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'label' => 'Admin Per Page Permissions',
                    'name' => 'admin.advanced_permissions',
                    'value' => '0',
                    'editable' => 1,
                    'hidden' => 0,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'label' => 'Default Template',
                    'name' => 'admin.default_template',
                    'value' => '2',
                    'editable' => 1,
                    'hidden' => 0,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'label' => 'Pre-fill Title Block',
                    'name' => 'admin.title_block',
                    'value' => 'title',
                    'editable' => 1,
                    'hidden' => 0,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'label' => 'Language',
                    'name' => 'frontend.language',
                    'value' => '1',
                    'editable' => 1,
                    'hidden' => 0,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'label' => 'Theme',
                    'name' => 'frontend.theme',
                    'value' => '1',
                    'editable' => 1,
                    'hidden' => 0,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'label' => 'WP Blog Url (relative ie. /blog/)',
                    'name' => 'blog.url',
                    'value' => '',
                    'editable' => 1,
                    'hidden' => 0,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'label' => 'Kontakt API Key for Beacons',
                    'name' => 'key.kontakt',
                    'value' => '',
                    'editable' => 1,
                    'hidden' => 1,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'label' => 'Bitly Access Token',
                    'name' => 'key.bitly',
                    'value' => '',
                    'editable' => 1,
                    'hidden' => 1,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'label' => 'Youtube Server API Key',
                    'name' => 'key.yt_server',
                    'value' => '',
                    'editable' => 1,
                    'hidden' => 1,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'label' => 'Youtube Browser API Key',
                    'name' => 'key.yt_browser',
                    'value' => '',
                    'editable' => 1,
                    'hidden' => 1,
                    'created_at' => $date,
                    'updated_at' => $date
                )
            )
        );

    }

    /**
     * Revert the changes to the database.
     *
     * @return void
     */
    public function down()
    {
        //
    }

}
