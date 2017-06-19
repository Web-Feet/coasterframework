<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class UpdateTemplateBlocks extends Migration
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
		Schema::table('template_blocks', function ($table) {
			
			$table->integer('template_id')->unsigned()->change();
			$table->integer('block_id')->unsigned()->change();
			
			$table->foreign('template_id')->references('id')->on('templates');
			$table->foreign('block_id')->references('id')->on('blocks');
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
