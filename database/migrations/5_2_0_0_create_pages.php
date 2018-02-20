<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePages extends Migration
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('template')->default(0)->unsigned();
            $table->integer('parent')->default(0)->unsigned();
            $table->integer('child_template')->default(0)->unsigned();
            $table->integer('order')->default(0);
            $table->boolean('group_container')->default(0);
            $table->integer('in_group')->default(0);
            $table->boolean('link')->default(0);
            $table->boolean('live')->default(1);
            $table->timestamp('live_start')->nullable();
            $table->timestamp('live_end')->nullable();
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
        Schema::drop('pages');
    }

}