<?php

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

        $date = new \DateTime;
        DB::table('admin_controllers')->insert(
            array(
                array(
                    'controller' => 'pagesearchlog',
                    'role_name' => 'Search log',
                    'role_order' => 5,
                    'role_section' => 1,
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
                    'inherit' => 0,
                    'edit_based' => 0,
                    'name' => 'Search Log',
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
        $controller = DB::table('admin_controllers')->select('id')->where('controller', '=', 'pagesearchlog')->first();
        $action = DB::table('admin_actions')->select('id')->where('name', '=', 'Search Log')->where('controller_id', '=', $controller->id)->first();


        DB::table('user_roles_actions')->where('action_id', '=', $action->id)->delete();
        DB::table('admin_actions')->where('id', '=', $action->id)->delete();
        DB::table('admin_controllers')->where('id', '=', $controller->id)->delete();
    }

}
