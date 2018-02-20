<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateBackups extends Migration
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('backups', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('log_id')->unsigned();
            $table->integer('primary_id');
            $table->string('model');
            $table->mediumText('data');
            $table->timestamps();
        });

        Schema::table('backups', function (Blueprint $table) {
            $table->foreign('log_id')
                  ->references('id')->on('admin_logs')
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
        Schema::drop('backups');
    }

}