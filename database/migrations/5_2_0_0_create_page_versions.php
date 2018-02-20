<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePageVersions extends Migration
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('page_versions', function (Blueprint $table) {
            $table->create();
            $table->increments('id');
            $table->integer('page_id');
            $table->integer('version_id');
            $table->string('template');
            $table->string('label')->nullable();
            $table->string('preview_key');
            $table->integer('user_id');
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