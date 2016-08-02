<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use CoasterCms\Models\Page;
use Carbon\Carbon;

class MovePageGroupPagesData extends Migration
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        $date = new Carbon;
        $addRows = [];

        foreach (Page::all() as $page) {
            if ($page->in_group) {
                $addRows[] = [
                    'page_id' => $page->id,
                    'group_id' => $page->in_group,
                    'created_at' => $date,
                    'updated_at' => $date
                ];
            }
        }

        DB::table('page_group_pages')->insert($addRows);
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn('in_group');
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