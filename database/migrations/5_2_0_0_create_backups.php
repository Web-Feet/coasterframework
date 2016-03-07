<?php

use Illuminate\Support\Facades\Schema;

class CreateBackups
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('backups', function ($table) {
            $table->create();
            $table->increments('id');
            $table->integer('log_id');
            $table->integer('primary_id');
            $table->string('model');
            $table->mediumText('data');
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