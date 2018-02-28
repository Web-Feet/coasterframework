<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RepeaterMaxRows extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('block_repeaters', function(Blueprint $table)
        {
            $table->integer('max_rows')->after('item_name')->nullable();
        });
    }

}
