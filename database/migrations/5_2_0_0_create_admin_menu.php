<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAdminMenu extends Migration
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('admin_menu', function (Blueprint $table) {
            $table->create();
            $table->increments('id');
            $table->integer('action_id');
            $table->integer('parent');
            $table->integer('order');
            $table->string('icon');
            $table->string('item_name');
            $table->string('item_desc')->nullable();
            $table->timestamps();
        });

        $date = new Carbon;

        DB::table('admin_menu')->insert(
            array(
                array(
                    'action_id' => 1,
                    'parent' => 0,
                    'order' => 1,
                    'icon' => 'fa fa-home',
                    'item_name' => 'Dashboard',
                    'item_desc' => '',
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'action_id' => 5,
                    'parent' => 0,
                    'order' => 2,
                    'icon' => 'fa fa-file-text-o',
                    'item_name' => 'Pages',
                    'item_desc' => '',
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'action_id' => 18,
                    'parent' => 0,
                    'order' => 3,
                    'icon' => 'fa fa-bars',
                    'item_name' => 'Menus',
                    'item_desc' => '',
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'action_id' => 25,
                    'parent' => 0,
                    'order' => 4,
                    'icon' => 'fa fa-globe',
                    'item_name' => 'Site-wide Content',
                    'item_desc' => '',
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'action_id' => 28,
                    'parent' => 0,
                    'order' => 5,
                    'icon' => 'fa fa-exchange',
                    'item_name' => 'Redirects',
                    'item_desc' => '',
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'action_id' => 26,
                    'parent' => 0,
                    'order' => 6,
                    'icon' => 'fa fa-folder-open',
                    'item_name' => 'File Manager',
                    'item_desc' => '',
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'action_id' => 39,
                    'parent' => 0,
                    'order' => 7,
                    'icon' => 'fa fa-user',
                    'item_name' => 'Users',
                    'item_desc' => '',
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'action_id' => 43,
                    'parent' => 0,
                    'order' => 8,
                    'icon' => 'fa fa-bullhorn',
                    'item_name' => 'Roles',
                    'item_desc' => '',
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'action_id' => 62,
                    'parent' => 0,
                    'order' => 9,
                    'icon' => 'fa fa-tint',
                    'item_name' => 'Theme',
                    'item_desc' => '',
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'action_id' => 64,
                    'parent' => 0,
                    'order' => 9,
                    'icon' => 'fa fa-bluetooth-b',
                    'item_name' => 'Beacons',
                    'item_desc' => '',
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