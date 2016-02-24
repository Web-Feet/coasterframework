<?php

use Illuminate\Support\Facades\Schema;

class CreatePageSearchLog
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('page_search_log', function ($table) {
            $table->create();
            $table->increments('id');
            $table->string('term');
            $table->integer('count');
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