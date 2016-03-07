<?php




class CreatePageBlocksDefault
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('page_blocks_default', function ($table) {
            $table->create();
            $table->increments('id');
            $table->integer('language_id')->default(1);
            $table->integer('block_id');
            $table->text('content');
            $table->integer('version');
            $table->timestamps();
        });

        $date = new \DateTime;

        DB::table('page_blocks_default')->insert(
            array(
                array(
                    'block_id' => 1,
                    'content' => '%page_name% | %site_name%',
                    'version' => 1,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'block_id' => 2,
                    'content' => '%page_name%',
                    'version' => 1,
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