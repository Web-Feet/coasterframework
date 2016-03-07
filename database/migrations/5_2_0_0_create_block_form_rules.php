<?php




class CreateBlockFormRules
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('block_form_rules', function ($table) {
            $table->create();
            $table->increments('id');
            $table->string('form_template');
            $table->string('field');
            $table->string('rule');
            $table->timestamps();
        });

        $date = new \DateTime;

        DB::table('block_form_rules')->insert(
            array(
                array(
                    'form_template' => 'contact',
                    'field' => 'name',
                    'rule' => 'required',
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'form_template' => 'contact',
                    'field' => 'email',
                    'rule' => 'required|email',
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'form_template' => 'contact',
                    'field' => 'message',
                    'rule' => 'required',
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