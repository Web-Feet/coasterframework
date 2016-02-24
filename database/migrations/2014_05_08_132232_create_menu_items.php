<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateMenuItems
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('menu_items', function ($table) {
            $table->create();
            $table->increments('id');
            $table->integer('menu_id');
            $table->string('page_id');
            $table->integer('order')->default(0);
            $table->integer('sub_levels')->default(0);
            $table->text('custom_name');
            $table->timestamps();
        });

        $date = new \DateTime;

        DB::table('menu_items')->insert(
            array(
                array(
                    'menu_id' => 1,
                    'page_id' => '1',
                    'order' => 1,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'menu_id' => 1,
                    'page_id' => '2',
                    'order' => 2,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'menu_id' => 1,
                    'page_id' => '3',
                    'order' => 3,
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