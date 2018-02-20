<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class BlockNoteNull extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('blocks', function(Blueprint $table)
        {
            $table->text('note')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('blocks', function(Blueprint $table)
        {
            $table->dropColumn('note');
        });
    }

}
