<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePageBlocks extends Migration
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('page_blocks', function (Blueprint $table) {
            $table->create();
            $table->increments('id');
            $table->integer('language_id')->default(1);
            $table->integer('page_id');
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