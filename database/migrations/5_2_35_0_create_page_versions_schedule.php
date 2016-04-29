<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreatePageVersionsSchedule
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('page_versions_schedule', function ($table) {
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