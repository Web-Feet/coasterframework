<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddThemeActions extends Migration
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
                    'action' => 'list',
                    'inherit' => 62,
                    'edit_based' => 0,
                    'name' => 'View Uploaded Themes',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 16,
                    'action' => 'export',
                    'inherit' => 0,
                    'edit_based' => 0,
                    'name' => 'Export Uploaded Themes',
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
                    'action_id' => $lastInsertId,
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

    }

}