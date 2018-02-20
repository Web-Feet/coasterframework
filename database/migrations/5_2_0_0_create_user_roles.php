<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateUserRoles extends Migration
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_roles', function (Blueprint $table) {
            $table->create();
            $table->increments('id');
            $table->string('name');
            $table->integer('admin')->default(1);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        $date = new Carbon;

        DB::table('user_roles')->insert(
            array(
                array(
                    'name' => 'Coaster Superuser',
                    'admin' => 2,
                    'description' => 'Unrestricted Account (can only be removed by superusers)',
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'name' => 'Coaster Admin',
                    'admin' => 1,
                    'description' => 'Default Admin Account',
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'name' => 'Coaster Editor',
                    'admin' => 1,
                    'description' => 'Default Editor Account',
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'name' => 'Coaster Account (Login Access Only)',
                    'admin' => 1,
                    'description' => 'Base Account With Login Access',
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'name' => 'Frontend Guest Account',
                    'admin' => 0,
                    'description' => 'Default Guest Account (no admin access)',
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