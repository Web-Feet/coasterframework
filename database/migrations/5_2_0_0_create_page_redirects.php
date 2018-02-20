<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePageRedirects extends Migration
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('page_redirects', function (Blueprint $table) {
            $table->create();
            $table->increments('id');
            $table->string('redirect');
            $table->string('to');
            $table->integer('type')->default(301);
            $table->integer('force')->default(0);
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