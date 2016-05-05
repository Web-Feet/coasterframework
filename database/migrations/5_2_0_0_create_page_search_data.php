<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePageSearchData extends Migration
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('page_search_data', function (Blueprint $table) {
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