<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateUsers
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function ($table) {
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
            $table->text('page_states');
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