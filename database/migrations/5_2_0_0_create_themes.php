<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateThemes
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('themes', function ($table) {
            $table->create();
            $table->increments('id');
            $table->string('theme');
            $table->timestamps();
        });

        $date = new \DateTime;

        DB::table('themes')->insert(
            array(
                array(
                    'theme' => 'default',
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