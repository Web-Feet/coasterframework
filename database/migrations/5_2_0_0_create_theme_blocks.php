<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateThemeBlocks extends Migration
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('theme_blocks', function (Blueprint $table) {
            $table->create();
            $table->increments('id');
            $table->integer('theme_id');
            $table->integer('block_id');
            $table->integer('show_in_pages')->default(0);
            $table->string('exclude_templates')->default(null);
            $table->integer('show_in_global')->default(1);
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