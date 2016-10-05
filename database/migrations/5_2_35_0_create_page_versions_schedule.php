<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePageVersionsSchedule extends Migration
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        $date = new Carbon;

        Schema::table('page_versions_schedule', function (Blueprint $table) {
            $table->create();
            $table->increments('id');
            $table->integer('page_version_id');
            $table->timestamp('live_from')->useCurrent();
            $table->integer('repeat_in')->default(0);
            $table->string('repeat_in_func')->nullable();
            $table->timestamps();
        });

        $controller = DB::table('admin_controllers')->select('id')->where('controller', '=', 'pages')->first();
        $action = DB::table('admin_actions')->select('id')->where('controller_id', '=', $controller->id)->where('action', '=', 'version-publish')->first();

        DB::table('admin_actions')->insert(
            array(
                array(
                    'controller_id' => $controller->id,
                    'action' => 'version-schedule',
                    'inherit' => $action->id,
                    'edit_based' => 0,
                    'name' => 'Schedule Versions',
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
        //
    }

}