<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateBlockRepeaters
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('block_repeaters', function ($table) {
            $table->create();
            $table->increments('id');
            $table->integer('block_id');
            $table->string('blocks');
            $table->timestamps();
        });

        $date = new \DateTime;

        DB::table('block_repeaters')->insert(
            array(
                array(
                    'block_id' => 13,
                    'blocks' => '15,16',
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