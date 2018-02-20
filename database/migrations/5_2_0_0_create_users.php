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
        Schema::create('users', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->boolean('active')->default(1);
            $table->string('password');
            $table->string('email')->unique();
            $table->integer('role_id')->unsigned();
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
        Schema::drop('users');
    }

}