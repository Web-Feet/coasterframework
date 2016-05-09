<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateUserRolesActions extends Migration
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_roles_actions', function (Blueprint $table) {
            $table->create();
            $table->increments('id');
            $table->integer('role_id');
            $table->integer('action_id');
            $table->timestamps();
        });

        $date = new Carbon;

        DB::table('user_roles_actions')->insert(
            array(
                array(
                    'role_id' => 2,
                    'action_id' => 5,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'role_id' => 2,
                    'action_id' => 6,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'role_id' => 2,
                    'action_id' => 7,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'role_id' => 2,
                    'action_id' => 8,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'role_id' => 2,
                    'action_id' => 9,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'role_id' => 2,
                    'action_id' => 10,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'role_id' => 2,
                    'action_id' => 18,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'role_id' => 2,
                    'action_id' => 19,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'role_id' => 2,
                    'action_id' => 20,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'role_id' => 2,
                    'action_id' => 21,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'role_id' => 2,
                    'action_id' => 22,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'role_id' => 2,
                    'action_id' => 25,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'role_id' => 2,
                    'action_id' => 26,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'role_id' => 2,
                    'action_id' => 27,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'role_id' => 2,
                    'action_id' => 28,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'role_id' => 2,
                    'action_id' => 29,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'role_id' => 2,
                    'action_id' => 31,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'role_id' => 2,
                    'action_id' => 32,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'role_id' => 2,
                    'action_id' => 33,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'role_id' => 2,
                    'action_id' => 34,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'role_id' => 2,
                    'action_id' => 35,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'role_id' => 2,
                    'action_id' => 39,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'role_id' => 2,
                    'action_id' => 40,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'role_id' => 2,
                    'action_id' => 43,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'role_id' => 2,
                    'action_id' => 49,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'role_id' => 2,
                    'action_id' => 62,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'role_id' => 2,
                    'action_id' => 64,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'role_id' => 2,
                    'action_id' => 65,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'role_id' => 3,
                    'action_id' => 5,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'role_id' => 3,
                    'action_id' => 6,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'role_id' => 3,
                    'action_id' => 8,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'role_id' => 3,
                    'action_id' => 18,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'role_id' => 3,
                    'action_id' => 19,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'role_id' => 3,
                    'action_id' => 22,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'role_id' => 3,
                    'action_id' => 25,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'role_id' => 3,
                    'action_id' => 26,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'role_id' => 3,
                    'action_id' => 27,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'role_id' => 3,
                    'action_id' => 28,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'role_id' => 3,
                    'action_id' => 29,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'role_id' => 3,
                    'action_id' => 31,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'role_id' => 3,
                    'action_id' => 32,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'role_id' => 3,
                    'action_id' => 33,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'role_id' => 3,
                    'action_id' => 34,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'role_id' => 3,
                    'action_id' => 39,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'role_id' => 4,
                    'action_id' => 31,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'role_id' => 4,
                    'action_id' => 32,
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