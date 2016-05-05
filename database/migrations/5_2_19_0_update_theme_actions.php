<?php

use Illuminate\Database\Migrations\Migration;

class UpdateThemeActions extends Migration
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {

        $date = new \DateTime;

        DB::table('admin_actions')->insert(
            array(
                array(
                    'controller_id' => 16,
                    'action' => 'forms',
                    'inherit' => 0,
                    'edit_based' => 0,
                    'name' => 'Change Form Rules',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                )
            )
        );

        $lastInsertId = DB::getPdo()->lastInsertId();

        DB::table('user_roles_actions')->insert(
            array(
                array(
                    'role_id' => 2,
                    'action_id' => 63,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'role_id' => 2,
                    'action_id' => $lastInsertId,
                    'created_at' => $date,
                    'updated_at' => $date
                )
            )
        );

        DB::table('admin_actions')->where('controller_id', '=', 16)->where('action', '=', 'update')->update(['inherit' => 0]);
        DB::table('admin_actions')->where('controller_id', '=', 16)->where('action', '=', 'index')->update(['name' => 'Show Theme Management']);

    }

    /**
     * Revert the changes to the database.
     *
     * @return void
     */
    public function down()
    {

    }

}