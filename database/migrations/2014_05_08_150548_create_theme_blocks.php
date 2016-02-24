<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateThemeBlocks
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('theme_blocks', function ($table) {
            $table->create();
            $table->increments('id');
            $table->integer('theme_id');
            $table->integer('block_id');
            $table->integer('show_in_pages')->default(0);
            $table->string('exclude_templates')->default(null);
            $table->integer('show_in_global')->default(1);
            $table->timestamps();
        });

        $date = new \DateTime;

        DB::table('theme_blocks')->insert(
            array(
                array(
                    'theme_id' => 1,
                    'block_id' => 1,
                    'show_in_pages' => 1,
                    'show_in_global' => 1,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'theme_id' => 1,
                    'block_id' => 2,
                    'show_in_pages' => 1,
                    'show_in_global' => 1,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'theme_id' => 1,
                    'block_id' => 3,
                    'show_in_pages' => 1,
                    'show_in_global' => 1,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'theme_id' => 1,
                    'block_id' => 4,
                    'show_in_pages' => 0,
                    'show_in_global' => 1,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'theme_id' => 1,
                    'block_id' => 5,
                    'show_in_pages' => 0,
                    'show_in_global' => 1,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'theme_id' => 1,
                    'block_id' => 6,
                    'show_in_pages' => 1,
                    'show_in_global' => 0,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'theme_id' => 1,
                    'block_id' => 7,
                    'show_in_pages' => 1,
                    'show_in_global' => 0,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'theme_id' => 1,
                    'block_id' => 8,
                    'show_in_pages' => 1,
                    'show_in_global' => 0,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'theme_id' => 1,
                    'block_id' => 9,
                    'show_in_pages' => 1,
                    'show_in_global' => 0,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'theme_id' => 1,
                    'block_id' => 10,
                    'show_in_pages' => 1,
                    'show_in_global' => 0,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'theme_id' => 1,
                    'block_id' => 18,
                    'show_in_pages' => 0,
                    'show_in_global' => 1,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'theme_id' => 1,
                    'block_id' => 18,
                    'show_in_pages' => 0,
                    'show_in_global' => 1,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'theme_id' => 1,
                    'block_id' => 19,
                    'show_in_pages' => 0,
                    'show_in_global' => 1,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'theme_id' => 1,
                    'block_id' => 20,
                    'show_in_pages' => 0,
                    'show_in_global' => 1,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'theme_id' => 1,
                    'block_id' => 21,
                    'show_in_pages' => 0,
                    'show_in_global' => 1,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'theme_id' => 1,
                    'block_id' => 22,
                    'show_in_pages' => 0,
                    'show_in_global' => 1,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'theme_id' => 1,
                    'block_id' => 23,
                    'show_in_pages' => 0,
                    'show_in_global' => 1,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'theme_id' => 1,
                    'block_id' => 24,
                    'show_in_pages' => 0,
                    'show_in_global' => 1,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'theme_id' => 1,
                    'block_id' => 25,
                    'show_in_pages' => 0,
                    'show_in_global' => 1,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'theme_id' => 1,
                    'block_id' => 26,
                    'show_in_pages' => 0,
                    'show_in_global' => 1,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'theme_id' => 1,
                    'block_id' => 27,
                    'show_in_pages' => 0,
                    'show_in_global' => 1,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'theme_id' => 1,
                    'block_id' => 28,
                    'show_in_pages' => 0,
                    'show_in_global' => 1,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'theme_id' => 1,
                    'block_id' => 29,
                    'show_in_pages' => 0,
                    'show_in_global' => 1,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'theme_id' => 1,
                    'block_id' => 30,
                    'show_in_pages' => 0,
                    'show_in_global' => 1,
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