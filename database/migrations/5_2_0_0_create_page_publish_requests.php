<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePagePublishRequests extends Migration
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('page_publish_requests', function (Blueprint $table) {
            $table->create();
            $table->increments('id');
            $table->integer('page_version_id');
            $table->enum('status', ['awaiting', 'approved', 'denied', 'cancelled']);
            $table->integer('user_id');
            $table->text('note');
            $table->integer('mod_id');
            $table->timestamps();
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