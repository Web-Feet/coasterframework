<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use CoasterCms\Models\Page;
use Carbon\Carbon;

class AddUserName extends Migration
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->nullable()->after('email');
        });

        $date = new Carbon;

        $controller = DB::table('admin_controllers')->select('id')->where('controller', '=', 'account')->first();
        DB::table('admin_actions')->insert(
            array(
                array(
                    'controller_id' => $controller->id,
                    'action' => 'name',
                    'inherit' => -1,
                    'edit_based' => 0,
                    'name' => 'Set Alias',
                    'about' => null,
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
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('name');
        });

        $controller = DB::table('admin_controllers')->select('id')->where('controller', '=', 'account')->first();
        DB::table('admin_actions')->where('controller_id', '=', $controller->id)->where('action', '=', 'name')->delete();
    }

}
