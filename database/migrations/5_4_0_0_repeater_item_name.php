<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RepeaterItemName extends Migration
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
            $table->string('item_name', 255)->after('blocks')->nullable();
        });
    }

}
