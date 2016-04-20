<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateTemplates
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('templates', function ($table) {
            $table->create();
            $table->increments('id');
            $table->integer('theme_id');
            $table->string('label');
            $table->string('template');
            $table->integer('child_template')->default(0);
            $table->integer('hidden')->default(0);
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