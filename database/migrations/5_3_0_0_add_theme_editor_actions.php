<?php
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;

class AddThemeEditorActions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      $date = new Carbon;

      DB::table('admin_actions')->insert(
          array(
              array(
                  'controller_id' => 16,
                  'action' => 'edit',
                  'inherit' => -1,
                  'edit_based' => 0,
                  'name' => 'Edit Theme',
                  'about' => null,
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
        DB::table('admin_actions')->where('controller_id', '=', 16)->where('action', '=', 'edit')->delete();
    }
}
