<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateTemplateBlocks
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('template_blocks', function ($table) {
            $table->create();
            $table->increments('id');
            $table->integer('template_id');
            $table->integer('block_id');
            $table->timestamps();
        });

        $date = new \DateTime;

        DB::table('template_blocks')->insert(
            array(
                array(
                    'template_id' => 1,
                    'block_id' => 11,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'template_id' => 3,
                    'block_id' => 16,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'template_id' => 3,
                    'block_id' => 17,
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