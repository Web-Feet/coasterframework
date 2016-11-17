<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateMenuItems extends Migration
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->create();
            $table->increments('id');
            $table->integer('menu_id');
            $table->string('page_id');
            $table->integer('order')->default(0);
            $table->integer('sub_levels')->default(0);
            $table->text('custom_name')->nullable();
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