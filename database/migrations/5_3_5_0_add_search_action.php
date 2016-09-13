<?php

use Illuminate\Database\Migrations\Migration;
use Carbon\Carbon;

class AddSearchAction extends Migration
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        $date = new Carbon;
        DB::table('admin_controllers')->insert(
            array(
                array(
                    'controller' => 'adminsearch',
                    'role_name' => 'Admin Search',
                    'role_order' => 0,
                    'role_section' => 2,
                    'created_at' => $date,
                    'updated_at' => $date
                )
            )
        );

        $controller = DB::table('admin_controllers')->select('id')->where('controller', '=', 'adminsearch')->first();
        DB::table('admin_actions')->insert(
            array(
                array(
                    'controller_id' => $controller->id,
                    'action' => 'index',
                    'inherit' => -1,
                    'edit_based' => 0,
                    'name' => 'Ajax Search',
                    'about' => null,
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
        $controller = DB::table('admin_controllers')->select('id')->where('controller', '=', 'adminsearch')->first();
        DB::table('admin_actions')->where('controller_id', '=', $controller->id)->where('action', '=', 'index')->delete();
        DB::table('admin_controllers')->where('id', '=', $controller->id)->delete();
    }

}
