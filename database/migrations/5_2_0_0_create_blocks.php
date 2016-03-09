<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
                    'order' => 20,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'category_id' => 2,
                    'label' => 'Meta Description',
                    'name' => 'meta_description',
                    'type' => 'text',
                    'order' => 30,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'category_id' => 2,
                    'label' => 'Meta Keywords',
                    'name' => 'meta_keywords',
                    'type' => 'text',
                    'order' => 40,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'category_id' => 4,
                    'label' => 'Header HTML',
                    'name' => 'header_html',
                    'type' => 'text',
                    'order' => 50,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'category_id' => 1,
                    'label' => 'Title',
                    'name' => 'title',
                    'type' => 'string',
                    'order' => 60,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'category_id' => 1,
                    'label' => 'Content',
                    'name' => 'content',
                    'type' => 'richtext',
                    'order' => 70,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'category_id' => 1,
                    'label' => 'Contact',
                    'name' => 'contact',
                    'type' => 'form',
                    'order' => 80,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'category_id' => 5,
                    'label' => 'Footer Left',
                    'name' => 'footer_left',
                    'type' => 'string',
                    'order' => 90,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'category_id' => 5,
                    'label' => 'Footer Right',
                    'name' => 'footer_right',
                    'type' => 'string',
                    'order' => 100,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'category_id' => 1,
                    'label' => 'Email Address',
                    'name' => 'email',
                    'type' => 'string',
                    'order' => 110,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'category_id' => 1,
                    'label' => 'Phone Number',
                    'name' => 'phone',
                    'type' => 'string',
                    'order' => 120,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'category_id' => 5,
                    'label' => 'Footer HTML',
                    'name' => 'footer_html',
                    'type' => 'text',
                    'order' => 130,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'category_id' => 3,
                    'label' => 'Banners',
                    'name' => 'banners',
                    'type' => 'repeater',
                    'order' => 60,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'category_id' => 1,
                    'label' => 'Image',
                    'name' => 'image',
                    'type' => 'image',
                    'order' => 80,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'category_id' => 1,
                    'label' => 'Slide Title',
                    'name' => 'slide_title',
                    'type' => 'string',
                    'order' => 20,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'category_id' => 1,
                    'label' => 'Slide Link',
                    'name' => 'slide_link',
                    'type' => 'link',
                    'order' => 30,
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