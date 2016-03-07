<?php

use Illuminate\Support\Facades\Schema;

class CreatePageSearchData
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('page_search_data', function ($table) {
            $table->create();
            $table->increments('id');
            $table->integer('language_id');
            $table->integer('page_id');
            $table->integer('block_id');
            $table->text('search_text');
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