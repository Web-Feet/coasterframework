<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreatePageLang
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('page_lang', function ($table) {
            $table->create();
            $table->increments('id');
            $table->integer('page_id');
            $table->integer('language_id');
            $table->string('url');
            $table->string('name');
            $table->integer('live_version');
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