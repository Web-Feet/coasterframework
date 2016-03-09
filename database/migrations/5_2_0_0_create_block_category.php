<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateBlockCategory
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('block_category', function ($table) {
            $table->create();
            $table->increments('id');
            $table->string('name');
            $table->integer('order');
            $table->timestamps();
        });

        $date = new \DateTime;

        DB::table('block_category')->insert(
            array(
                array(
                    'name' => 'Main Content',
                    'order' => 10,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'name' => 'SEO Content',
                    'order' => 100,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'name' => 'Banners',
                    'order' => 20,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'name' => 'Header',
                    'order' => 30,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'name' => 'Footer',
                    'order' => 40,
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