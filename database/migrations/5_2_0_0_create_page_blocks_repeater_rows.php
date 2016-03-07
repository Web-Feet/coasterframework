<?php

use Illuminate\Support\Facades\Schema;

class CreatePageBlocksRepeaterRows
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('page_blocks_repeater_rows', function ($table) {
            $table->create();
            $table->increments('id');
            $table->integer('repeater_id');
            $table->integer('row_id');
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