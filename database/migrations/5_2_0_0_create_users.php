<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateUsers extends Migration
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->create();
            $table->increments('id');
            $table->integer('active')->default(1);
            $table->string('password');
            $table->string('email');
            $table->integer('role_id');
            $table->rememberToken();
            $table->string('tmp_code')->nullable();
            $table->timestamp('tmp_code_created')->nullable();
            $table->string('blog_login')->nullable();
            $table->text('page_states')->nullable();
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