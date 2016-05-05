<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePageVersionsSchedule extends Migration
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('page_versions_schedule', function (Blueprint $table) {
            $table->create();
            $table->increments('id');
            $table->integer('page_version_id');
            $table->timestamp('live_from');
            $table->integer('repeat_in')->default(0);
            $table->string('repeat_in_func')->nullable();
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