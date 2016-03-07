<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateAdminLogs
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('admin_logs', function ($table) {
            $table->create();
            $table->increments('id');
            $table->integer('user_id');
            $table->string('log');
            $table->timestamps();
        });

        $date = new \DateTime;

        DB::table('admin_logs')->insert(
            array(
                array(
                    'user_id' => 1,
                    'log' => 'Setup CMS',
                    'created_at' => $date,
                    'updated_at' => $date
                )
            )
        );
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