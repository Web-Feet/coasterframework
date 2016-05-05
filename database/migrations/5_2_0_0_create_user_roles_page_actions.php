<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateUserRolesPageActions extends Migration
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_roles_page_actions', function (Blueprint $table) {
            $table->create();
            $table->increments('id');
            $table->integer('role_id');
            $table->integer('page_id');
            $table->integer('action_id');
            $table->enum('access', array('allow', 'deny'));
            $table->timestamps();
        });
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