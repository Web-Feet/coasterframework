<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAdminLogs extends Migration
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('admin_logs', function (Blueprint $table) {
            $table->create();
            $table->increments('id');
            $table->integer('user_id');
            $table->string('log');
            $table->timestamps();
        });

        $date = new Carbon;

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