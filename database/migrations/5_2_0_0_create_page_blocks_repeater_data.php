<?php



class CreatePageBlocksRepeaterData
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('page_blocks_repeater_data', function ($table) {
            $table->create();
            $table->increments('id');
            $table->integer('row_key');
            $table->integer('block_id');
            $table->text('content');
            $table->integer('version');
            $table->timestamps();
        });

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