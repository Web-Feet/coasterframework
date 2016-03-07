<?php




class CreatePages
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pages', function ($table) {
            $table->create();
            $table->increments('id');
            $table->integer('template')->default(0);
            $table->integer('parent')->default(0);
            $table->integer('child_template')->default(0);
            $table->integer('order')->default(0);
            $table->integer('group_container')->default(0);
            $table->integer('in_group')->default(0);
            $table->integer('link')->default(0);
            $table->integer('live')->default(1);
            $table->timestamp('live_start')->nullable();
            $table->timestamp('live_end')->nullable();
            $table->timestamps();
        });

        $date = new \DateTime;

        DB::table('pages')->insert(
            array(
                array(
                    'template' => 1,
                    'parent' => 0,
                    'order' => 1,
                    'group_container' => 0,
                    'in_group' => 0,
                    'link' => 0,
                    'live' => 1,
                    'live_start' => null,
                    'live_end' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'template' => 2,
                    'parent' => 0,
                    'order' => 1,
                    'group_container' => 0,
                    'in_group' => 0,
                    'link' => 0,
                    'live' => 1,
                    'live_start' => null,
                    'live_end' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'template' => 3,
                    'parent' => 0,
                    'order' => 1,
                    'group_container' => 0,
                    'in_group' => 0,
                    'link' => 0,
                    'live' => 1,
                    'live_start' => null,
                    'live_end' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'template' => 2,
                    'parent' => 3,
                    'order' => 1,
                    'group_container' => 0,
                    'in_group' => 0,
                    'link' => 0,
                    'live' => 1,
                    'live_start' => null,
                    'live_end' => null,
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