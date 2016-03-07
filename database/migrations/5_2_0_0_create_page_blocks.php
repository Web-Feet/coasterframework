<?php




class CreatePageBlocks
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('page_blocks', function ($table) {
            $table->create();
            $table->increments('id');
            $table->integer('language_id')->default(1);
            $table->integer('page_id');
            $table->integer('block_id');
            $table->text('content');
            $table->integer('version');
            $table->timestamps();
        });

        $date = new \DateTime;

        DB::table('page_blocks')->insert(
            array(
                array(
                    'page_id' => 1,
                    'block_id' => 6,
                    'content' => 'Welcome to ...',
                    'version' => 1,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'page_id' => 1,
                    'block_id' => 9,
                    'content' => 'At vero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis praesentium voluptatum deleniti atque ...',
                    'version' => 1,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'page_id' => 2,
                    'block_id' => 6,
                    'content' => 'About Us',
                    'version' => 1,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'page_id' => 2,
                    'block_id' => 9,
                    'content' => 'We are ...',
                    'version' => 1,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'page_id' => 3,
                    'block_id' => 6,
                    'content' => 'Contact Us',
                    'version' => 1,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'page_id' => 3,
                    'block_id' => 16,
                    'content' => 'Contact Form',
                    'version' => 1,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'page_id' => 3,
                    'block_id' => 17,
                    'content' => 'O:8:"stdClass":5:{s:10:"email_from";s:0:"";s:8:"email_to";s:0:"";s:8:"template";s:7:"contact";s:7:"page_to";s:1:"4";s:7:"captcha";b:0;}',
                    'version' => 1,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'page_id' => 4,
                    'block_id' => 6,
                    'content' => 'Contact Confirmation',
                    'version' => 1,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'page_id' => 4,
                    'block_id' => 9,
                    'content' => 'Thank you for contacting us, we will get back to you shortly.',
                    'version' => 1,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
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