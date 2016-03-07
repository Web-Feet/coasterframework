<?php



class CreateBlockVideoCache
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('block_video_cache', function ($table) {
            $table->create();
            $table->increments('id');
            $table->string('videoId');
            $table->text('videoInfo');
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