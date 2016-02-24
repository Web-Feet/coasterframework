<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreatePageLang
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('page_lang', function ($table) {
            $table->create();
            $table->increments('id');
            $table->integer('page_id');
            $table->integer('language_id');
            $table->string('url');
            $table->string('name');
            $table->integer('live_version');
            $table->timestamps();
        });

        $date = new \DateTime;

        DB::table('page_lang')->insert(
            array(
                array(
                    'page_id' => 1,
                    'language_id' => 1,
                    'url' => '/',
                    'name' => 'Home',
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'page_id' => 2,
                    'language_id' => 1,
                    'url' => 'about',
                    'name' => 'About Us',
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'page_id' => 3,
                    'language_id' => 1,
                    'url' => 'contact',
                    'name' => 'Contact Us',
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'page_id' => 4,
                    'language_id' => 1,
                    'url' => 'confirm',
                    'name' => 'Thank You',
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