<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAdminControllers extends Migration
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {

        Schema::table('admin_controllers', function (Blueprint $table) {
            $table->create();
            $table->increments('id');
            $table->string('controller');
            $table->string('role_name');
            $table->integer('role_order');
            $table->integer('role_section');
            $table->timestamps();
        });

        $date = new Carbon;

        DB::table('admin_controllers')->insert(
            array(
                array(
                    'controller' => 'home',
                    'role_name' => 'Dashboard',
                    'role_order' => 1,
                    'role_section' => 1,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller' => 'pages',
                    'role_name' => 'Pages',
                    'role_order' => 1,
                    'role_section' => 1,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller' => 'groups',
                    'role_name' => 'Groups',
                    'role_order' => 1,
                    'role_section' => 1,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller' => 'menus',
                    'role_name' => 'Menus',
                    'role_order' => 2,
                    'role_section' => 1,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller' => 'blocks',
                    'role_name' => 'Site-wide Content',
                    'role_order' => 3,
                    'role_section' => 1,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller' => 'filemanager',
                    'role_name' => 'Filemanager',
                    'role_order' => 4,
                    'role_section' => 1,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller' => 'redirects',
                    'role_name' => 'Redirects',
                    'role_order' => 5,
                    'role_section' => 1,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller' => 'account',
                    'role_name' => 'User Account',
                    'role_order' => 1,
                    'role_section' => 2,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller' => 'system',
                    'role_name' => 'System Settings',
                    'role_order' => 2,
                    'role_section' => 2,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller' => 'users',
                    'role_name' => 'Users',
                    'role_order' => 1,
                    'role_section' => 3,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller' => 'roles',
                    'role_name' => 'Roles',
                    'role_order' => 2,
                    'role_section' => 3,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller' => 'backups',
                    'role_name' => 'Backups',
                    'role_order' => 3,
                    'role_section' => 3,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller' => 'repeaters',
                    'role_name' => 'Repeaters',
                    'role_order' => 1,
                    'role_section' => 1,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller' => 'gallery',
                    'role_name' => 'Galleries',
                    'role_order' => 1,
                    'role_section' => 1,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller' => 'forms',
                    'role_name' => 'Forms',
                    'role_order' => 1,
                    'role_section' => 1,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller' => 'themes',
                    'role_name' => 'Themes',
                    'role_order' => 3,
                    'role_section' => 2,
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