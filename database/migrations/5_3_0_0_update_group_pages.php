<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use CoasterCms\Models\Page;
use Carbon\Carbon;

class UpdateGroupPages extends Migration
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
            $table->integer('group_container_url_priority')->default(0)->after('group_container');
            $table->integer('canonical_parent')->default(0)->after('group_container_url_priority');
        });

        Schema::table('page_group', function (Blueprint $table) {
            $table->dropColumn('default_parent');
            $table->dropColumn('order_by_attribute_id');
            $table->dropColumn('order_dir');
            $table->integer('url_priority')->default(50)->after('item_name');
        });

        Schema::table('page_group_attributes', function (Blueprint $table) {
            $table->integer('item_block_order_priority')->default(0)->after('item_block_id');
            $table->string('item_block_order_dir')->default('asc')->after('item_block_order_priority');
            $table->integer('filter_by_block_id')->default(0)->change();
        });

        $groupsController = DB::table('admin_controllers')->select('id')->where('controller', '=', 'groups')->first();
        DB::table('admin_actions')->insert(
            array(
                array(
                    'controller_id' => $groupsController->id,
                    'action' => 'edit',
                    'inherit' => 0,
                    'edit_based' => 0,
                    'name' => 'Edit Group Settings',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                )
            )
        );
        $lastInsertId = DB::getPdo()->lastInsertId();
        DB::table('user_roles_actions')->insert(
            array(
                array(
                    'role_id' => 2,
                    'action_id' => $lastInsertId,
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
