<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateMenus
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('menus', function ($table) {
            $table->create();
            $table->increments('id');
            $table->string('label');
            $table->string('name');
            $table->integer('max_sublevel')->default(0);
            $table->timestamps();
        });

        $date = new \DateTime;

        DB::table('menus')->insert(
            array(
                array(
                    'label' => 'Main Menu',
                    'name' => 'main_menu',
                    'max_sublevel' => 1,
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