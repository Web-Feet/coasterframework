<?php

use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddEstimoteSupport extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('block_beacons', function(Blueprint $table)
        {
            $table->string('type')->after('id')->default('Kontakt');
        });
        $date = new Carbon;
        DB::table('settings')->insert([
            [
                'label' => 'Estimote APP ID',
                'name' => 'appid.estimote',
                'value' => '',
                'editable' => 1,
                'hidden' => 0,
                'created_at' => $date,
                'updated_at' => $date
            ],
            [
                'label' => 'Estimote API Key',
                'name' => 'key.estimote',
                'value' => '',
                'editable' => 1,
                'hidden' => 0,
                'created_at' => $date,
                'updated_at' => $date
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('block_beacons', function(Blueprint $table)
        {
            $table->dropColumn('type');
        });
        DB::table('settings')->whereIn('name', ['appid.estimote', 'key.estimote'])->delete();
    }

}
