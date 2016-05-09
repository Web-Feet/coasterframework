<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;

class UpdateSearchLogAdmin extends Migration
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
                    'controller' => 'search',
                    'role_name' => 'Search log',
                    'role_order' => 4,
                    'role_section' => 3,
                    'created_at' => $date,
                    'updated_at' => $date
                )
            )
        );
        $controller_id = DB::getPdo()->lastInsertId();
        DB::table('admin_actions')->insert(
            array(
                array(
                    'controller_id' => $controller_id,
                    'action' => 'index',
                    'inherit' => -1,
                    'edit_based' => 0,
                    'name' => 'View Search Log',
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
        $controller = DB::table('admin_controllers')->select('id')->where('controller', '=', 'search')->first();
        $action = DB::table('admin_actions')->select('id')->where('name', '=', 'Search Log')->where('controller_id', '=', $controller->id)->first();


        DB::table('user_roles_actions')->where('action_id', '=', $action->id)->delete();
        DB::table('admin_actions')->where('id', '=', $action->id)->delete();
        DB::table('admin_controllers')->where('id', '=', $controller->id)->delete();
    }

}
