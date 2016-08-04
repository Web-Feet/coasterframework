<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use CoasterCms\Models\Page;

class AlterGroupUrlPriorities extends Migration
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('page_group_pages', function (Blueprint $table) {
            $table->integer('url_priority')->after('group_id');
        });

        Schema::table('page_group', function (Blueprint $table) {
            $table->dropColumn('default_parent');
            $table->integer('url_priority')->default(50)->after('item_name');
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