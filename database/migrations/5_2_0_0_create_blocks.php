<?php




class CreateBlocks
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('blocks', function ($table) {
            $table->create();
            $table->increments('id');
            $table->integer('category_id');
            $table->string('label');
            $table->string('name');
            $table->string('type');
            $table->integer('order')->default(0);
            $table->integer('search_weight')->default(1);
            $table->integer('active')->default(1);
            $table->timestamps();
        });

        $date = new \DateTime;

        DB::table('blocks')->insert(
            array(
                array(
                    'category_id' => 2,
                    'label' => 'Meta Title',
                    'name' => 'meta_title',
                    'type' => 'string',
                    'order' => 1,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'category_id' => 2,
                    'label' => 'Meta Description',
                    'name' => 'meta_description',
                    'type' => 'text',
                    'order' => 2,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'category_id' => 2,
                    'label' => 'Meta Keywords',
                    'name' => 'meta_keywords',
                    'type' => 'text',
                    'order' => 3,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'category_id' => 2,
                    'label' => 'Header HTML',
                    'name' => 'header_html',
                    'type' => 'text',
                    'order' => 11,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'category_id' => 2,
                    'label' => 'Footer HTML',
                    'name' => 'footer_html',
                    'type' => 'text',
                    'order' => 12,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'category_id' => 1,
                    'label' => 'Main Title',
                    'name' => 'title',
                    'type' => 'string',
                    'order' => 1,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'category_id' => 1,
                    'label' => 'Intro Text',
                    'name' => 'intro',
                    'type' => 'text',
                    'order' => 2,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'category_id' => 1,
                    'label' => 'Main Image',
                    'name' => 'image',
                    'type' => 'image',
                    'order' => 3,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'category_id' => 1,
                    'label' => 'Main Content',
                    'name' => 'content',
                    'type' => 'richtext',
                    'order' => 4,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'category_id' => 1,
                    'label' => 'Main Link',
                    'name' => 'link',
                    'type' => 'link',
                    'order' => 5,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'category_id' => 3,
                    'label' => 'Banners',
                    'name' => 'banners',
                    'type' => 'repeater',
                    'order' => 1,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'category_id' => 0,
                    'label' => 'Slide Title',
                    'name' => 'slide_title',
                    'type' => 'string',
                    'order' => 1,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'category_id' => 0,
                    'label' => 'Slide Text',
                    'name' => 'slide_text',
                    'type' => 'text',
                    'order' => 2,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'category_id' => 0,
                    'label' => 'Slide Image',
                    'name' => 'slide_image',
                    'type' => 'image',
                    'order' => 3,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'category_id' => 0,
                    'label' => 'Slide Link',
                    'name' => 'slide_link',
                    'type' => 'link',
                    'order' => 4,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'category_id' => 1,
                    'label' => 'Contact Form Title',
                    'name' => 'contact_title',
                    'type' => 'string',
                    'order' => 11,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'category_id' => 1,
                    'label' => 'Contact Form',
                    'name' => 'contact',
                    'type' => 'form',
                    'order' => 12,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'category_id' => 5,
                    'label' => 'Footer Title',
                    'name' => 'footer_title',
                    'type' => 'string',
                    'order' => 1,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'category_id' => 5,
                    'label' => 'Footer Text',
                    'name' => 'footer_text',
                    'type' => 'text',
                    'order' => 2,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'category_id' => 5,
                    'label' => 'Footer Left',
                    'name' => 'footer_left',
                    'type' => 'string',
                    'order' => 3,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'category_id' => 5,
                    'label' => 'Footer Right',
                    'name' => 'footer_right',
                    'type' => 'string',
                    'order' => 4,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'category_id' => 6,
                    'label' => 'Email Address',
                    'name' => 'email',
                    'type' => 'string',
                    'order' => 1,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'category_id' => 6,
                    'label' => 'Phone Number',
                    'name' => 'phone',
                    'type' => 'string',
                    'order' => 2,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'category_id' => 6,
                    'label' => 'Address',
                    'name' => 'address',
                    'type' => 'text',
                    'order' => 3,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'category_id' => 6,
                    'label' => 'Facebook Link',
                    'name' => 'facebook',
                    'type' => 'link',
                    'order' => 11,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'category_id' => 6,
                    'label' => 'Twitter Link',
                    'name' => 'twitter',
                    'type' => 'link',
                    'order' => 12,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'category_id' => 6,
                    'label' => 'Google+ Link',
                    'name' => 'google',
                    'type' => 'link',
                    'order' => 13,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'category_id' => 6,
                    'label' => 'Youtube Link',
                    'name' => 'youtube',
                    'type' => 'link',
                    'order' => 15,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'category_id' => 6,
                    'label' => 'LinkedIn Link',
                    'name' => 'linkedin',
                    'type' => 'link',
                    'order' => 14,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'category_id' => 6,
                    'label' => 'Rss Feed',
                    'name' => 'rssfeed',
                    'type' => 'link',
                    'order' => 16,
                    'created_at' => $date,
                    'updated_at' => $date
                )
            )
        );

        // give title more search weight
        DB::table('blocks')->where('name', '=', 'title')
            ->update(array('search_weight' => 5));
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