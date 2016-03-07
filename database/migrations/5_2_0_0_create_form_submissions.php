<?php



class CreateFormSubmissions
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('form_submissions', function ($table) {
            $table->create();
            $table->increments('id');
            $table->integer('form_block_id');
            $table->integer('from_page_id');
            $table->text('content');
            $table->integer('sent');
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