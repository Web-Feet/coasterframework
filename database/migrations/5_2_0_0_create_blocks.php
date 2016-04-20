<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateBlocks
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('blocks', function ($table) {
            $table->create();
            $table->increments('id');
            $table->integer('category_id');
            $table->string('label');
            $table->string('name');
            $table->string('type');
            $table->integer('order')->default(0);
            $table->integer('search_weight')->default(1);
            $table->integer('active')->default(1);
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