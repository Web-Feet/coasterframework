<?php

use Illuminate\Support\Facades\Schema;

class CreateBlockSelectopts
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('block_selectopts', function ($table) {
            $table->create();
            $table->increments('id');
            $table->integer('block_id');
            $table->string('option');
            $table->string('value');
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