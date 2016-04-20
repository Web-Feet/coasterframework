<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreatePageVersions
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('page_versions', function ($table) {
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