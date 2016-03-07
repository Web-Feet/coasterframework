<?php




class CreateTemplates
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('templates', function ($table) {
            $table->create();
            $table->increments('id');
            $table->integer('theme_id');
            $table->string('label');
            $table->string('template');
            $table->integer('child_template')->default(0);
            $table->integer('hidden')->default(0);
            $table->timestamps();
        });

        $date = new \DateTime;

        DB::table('templates')->insert(
            array(
                array(
                    'theme_id' => '1',
                    'label' => 'Home Page Template',
                    'template' => 'home',
                    'hidden' => 0,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'theme_id' => '1',
                    'label' => 'Internal Page Template',
                    'template' => 'internal',
                    'hidden' => 0,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'theme_id' => '1',
                    'label' => 'Contact Form Template',
                    'template' => 'contact',
                    'hidden' => 0,
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