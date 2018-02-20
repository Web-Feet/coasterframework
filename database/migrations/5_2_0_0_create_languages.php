<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateLanguages extends Migration
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('languages', function (Blueprint $table) {
            $table->create();
            $table->increments('id');
            $table->string('language');
            $table->timestamps();
        });

        $date = new Carbon;

        DB::table('languages')->insert(
            array(
                array(
                    'language' => 'English',
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