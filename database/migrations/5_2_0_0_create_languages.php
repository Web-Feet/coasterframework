<?php




class CreateLanguages
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('languages', function ($table) {
            $table->create();
            $table->increments('id');
            $table->string('language');
            $table->timestamps();
        });

        $date = new \DateTime;

        DB::table('languages')->insert(
            array(
                array(
                    'language' => 'English',
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