<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddPagesSitemap extends Migration
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pages', function(Blueprint $table)
        {
            $table->integer('sitemap')->after('live')->default(1);
        });
    }

    /**
     * Revert the changes to the database.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pages', function(Blueprint $table)
        {
            $table->dropColumn('sitemap');
        });
    }

}