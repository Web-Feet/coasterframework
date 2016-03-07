<?php




class CreatePageVersions
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('page_versions', function ($table) {
            $table->create();
            $table->increments('id');
            $table->integer('page_id');
            $table->integer('version_id');
            $table->string('template');
            $table->string('label')->nullable();
            $table->string('preview_key');
            $table->integer('user_id');
            $table->timestamps();
        });

        $date = new \DateTime;

        DB::table('page_versions')->insert(
            array(
                array(
                    'page_id' => 0,
                    'version_id' => 1,
                    'template' => 0,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'page_id' => 1,
                    'version_id' => 1,
                    'template' => 1,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'page_id' => 2,
                    'version_id' => 1,
                    'template' => 2,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'page_id' => 3,
                    'version_id' => 1,
                    'template' => 3,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'page_id' => 4,
                    'version_id' => 1,
                    'template' => 2,
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