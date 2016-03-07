<?php




class CreateBlockCategory
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('block_category', function ($table) {
            $table->create();
            $table->increments('id');
            $table->string('name');
            $table->integer('order');
            $table->timestamps();
        });

        $date = new \DateTime;

        DB::table('block_category')->insert(
            array(
                array(
                    'name' => 'Main Content',
                    'order' => 1,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'name' => 'SEO Content',
                    'order' => 11,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'name' => 'Banners',
                    'order' => 2,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'name' => 'Header',
                    'order' => 3,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'name' => 'Footer',
                    'order' => 4,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'name' => 'Contact Details',
                    'order' => 5,
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