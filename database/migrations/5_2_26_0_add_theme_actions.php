<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;

class AddThemeActions extends Migration
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {

        $date = new Carbon;

        $themesController = DB::table('admin_controllers')->select('id')->where('controller', '=', 'themes')->first();
        $indexAction = DB::table('admin_actions')->select('id')->where('controller_id', '=', $themesController->id)->where('action', '=', 'index')->first();

        DB::table('admin_actions')->insert(
            array(
                array(
                    'controller_id' => $themesController->id,
                    'action' => 'list',
                    'inherit' => $indexAction->id,
                    'edit_based' => 0,
                    'name' => 'View Uploaded Themes',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => $themesController->id,
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