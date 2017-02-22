<?php

use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCacheSetting extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $date = new Carbon;

        DB::table('settings')->insert(
            array(
                array(
                    'label' => 'Cache Length (Minutes)',
                    'name' => 'frontend.cache',
                    'value' => '240',
                    'editable' => 1,
                    'hidden' => 0,
                    'created_at' => $date,
                    'updated_at' => $date
                )
            )
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

    }

}
