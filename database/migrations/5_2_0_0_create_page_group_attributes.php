<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePageGroupAttributes extends Migration
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('page_group_attributes', function (Blueprint $table) {
            $table->create();
            $table->increments('id');
            $table->integer('group_id');
            $table->integer('item_block_id');
            $table->integer('filter_by_block_id');
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