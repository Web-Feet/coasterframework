<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateBlocks extends Migration
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('blocks', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('category_id')->unsigned();
            $table->string('label');
            $table->string('name')->unique();
            $table->string('type');
            $table->integer('order')->default(0);
            $table->integer('search_weight')->default(1);
            $table->integer('active')->default(1);
            $table->timestamps();
        });

        Schema::table('blocks', function (Blueprint $table) {
            $table->foreign('category_id')
                  ->references('id')->on('block_category')
                  ->onDelete('cascade');
        });
    }

    /**
     * Revert the changes to the database.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('blocks');
    }

}