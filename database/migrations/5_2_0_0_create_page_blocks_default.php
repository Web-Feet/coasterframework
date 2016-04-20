<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreatePageBlocksDefault
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('page_blocks_default', function ($table) {
            $table->create();
            $table->increments('id');
            $table->integer('language_id')->default(1);
            $table->integer('block_id');
            $table->text('content');
            $table->integer('version');
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