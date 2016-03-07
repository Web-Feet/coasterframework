<?php



class CreatePageGroup
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('page_group', function ($table) {
            $table->create();
            $table->increments('id');
            $table->string('name');
            $table->string('item_name');
            $table->integer('default_parent');
            $table->integer('default_template');
            $table->integer('order_by_attribute_id')->nullable();
            $table->string('order_dir')->nullable();
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