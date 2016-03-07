<?php



class CreateBlockBeacons
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('block_beacons', function ($table) {
            $table->create();
            $table->increments('id');
            $table->string('unique_id');
            $table->integer('page_id');
            $table->text('url');
            $table->integer('removed')->default(0);
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