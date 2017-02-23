<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class HideSubpageInMenu extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('menu_items', function(Blueprint $table)
        {
            $table->string('hidden_pages')->after('custom_page_names')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('menu_items', function(Blueprint $table)
        {
            $table->dropColumn('hidden_pages');
        });
    }

}
