<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAdminActions extends Migration
{

    /**
     * Make changes to the database.
     *
     * @return void
     */
    public function up()
    {

        Schema::table('admin_actions', function (Blueprint $table) {
            $table->create();
            $table->increments('id');
            $table->integer('controller_id');
            $table->string('action');
            $table->integer('inherit')->default(0);
            $table->integer('edit_based')->default(0);
            $table->string('name');
            $table->text('about')->nullable();
            $table->timestamps();
        });

        $date = new Carbon;

        DB::table('admin_actions')->insert(
            array(
                array(
                    'controller_id' => 1,
                    'action' => 'index',
                    'inherit' => -1,
                    'edit_based' => 0,
                    'name' => 'Dashboard',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 1,
                    'action' => 'logs',
                    'inherit' => -1,
                    'edit_based' => 0,
                    'name' => 'View Admin Logs',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 1,
                    'action' => 'your-requests',
                    'inherit' => -1,
                    'edit_based' => 0,
                    'name' => 'View publish requests',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 1,
                    'action' => 'requests',
                    'inherit' => -1,
                    'edit_based' => 0,
                    'name' => 'View requests to moderate',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 2,
                    'action' => 'index',
                    'inherit' => 0,
                    'edit_based' => 0,
                    'name' => 'Show Page Management',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 2,
                    'action' => 'sort',
                    'inherit' => 0,
                    'edit_based' => 0,
                    'name' => 'Sort Pages',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 2,
                    'action' => 'add',
                    'inherit' => 0,
                    'edit_based' => 0,
                    'name' => 'Add Pages',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 2,
                    'action' => 'edit',
                    'inherit' => 0,
                    'edit_based' => 0,
                    'name' => 'Edit Pages',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 2,
                    'action' => 'delete',
                    'inherit' => 0,
                    'edit_based' => 0,
                    'name' => 'Delete Pages',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 2,
                    'action' => 'version-publish',
                    'inherit' => 0,
                    'edit_based' => 0,
                    'name' => 'Publish Versions',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 2,
                    'action' => 'version-rename',
                    'inherit' => 0,
                    'edit_based' => 1,
                    'name' => 'Rename Versions',
                    'about' => 'required to be logged is as author or have publishing permission',
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 2,
                    'action' => 'versions',
                    'inherit' => 0,
                    'edit_based' => 1,
                    'name' => 'Ajax Versions Table',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 2,
                    'action' => 'request-publish',
                    'inherit' => 0,
                    'edit_based' => 1,
                    'name' => 'Make Requests',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 2,
                    'action' => 'request-publish-action',
                    'inherit' => 0,
                    'edit_based' => 1,
                    'name' => 'Action Requests (cancel/approve/deny)',
                    'about' => 'required to be logged in as author to cancel or else have publish permissions',
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 2,
                    'action' => 'requests',
                    'inherit' => 0,
                    'edit_based' => 1,
                    'name' => 'Ajax Requests Table',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 2,
                    'action' => 'tinymce-page-list',
                    'inherit' => 5,
                    'edit_based' => 0,
                    'name' => 'TinyMce Page Links',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 3,
                    'action' => 'pages',
                    'inherit' => 5,
                    'edit_based' => 0,
                    'name' => 'List Group Pages',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 4,
                    'action' => 'index',
                    'inherit' => 0,
                    'edit_based' => 0,
                    'name' => 'Show Menu Items',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 4,
                    'action' => 'sort',
                    'inherit' => 0,
                    'edit_based' => 0,
                    'name' => 'Sort Menu Items',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 4,
                    'action' => 'add',
                    'inherit' => 0,
                    'edit_based' => 0,
                    'name' => 'Add Menu Items',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 4,
                    'action' => 'delete',
                    'inherit' => 0,
                    'edit_based' => 0,
                    'name' => 'Delete Menu Items',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 4,
                    'action' => 'rename',
                    'inherit' => 0,
                    'edit_based' => 0,
                    'name' => 'Rename Menu Items',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 4,
                    'action' => 'get-levels',
                    'inherit' => 19,
                    'edit_based' => 0,
                    'name' => 'Get Subpage Level',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 4,
                    'action' => 'save-levels',
                    'inherit' => 22,
                    'edit_based' => 0,
                    'name' => 'Set Subpage Level',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 5,
                    'action' => 'index',
                    'inherit' => 0,
                    'edit_based' => 0,
                    'name' => 'Edit Site-wide Content',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 6,
                    'action' => 'index',
                    'inherit' => 0,
                    'edit_based' => 0,
                    'name' => 'View Files',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 6,
                    'action' => 'edit',
                    'inherit' => 0,
                    'edit_based' => 0,
                    'name' => 'Manage Files',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 7,
                    'action' => 'index',
                    'inherit' => 0,
                    'edit_based' => 0,
                    'name' => 'View Redirects',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 7,
                    'action' => 'edit',
                    'inherit' => 0,
                    'edit_based' => 0,
                    'name' => 'Manage Redirects',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 7,
                    'action' => 'import',
                    'inherit' => 29,
                    'edit_based' => 0,
                    'name' => 'Import Redirects',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 8,
                    'action' => 'index',
                    'inherit' => 0,
                    'edit_based' => 0,
                    'name' => 'Show Account Settings',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 8,
                    'action' => 'password',
                    'inherit' => 0,
                    'edit_based' => 0,
                    'name' => 'Change Password',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 8,
                    'action' => 'blog',
                    'inherit' => 0,
                    'edit_based' => 0,
                    'name' => 'Auto Blog Login',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 9,
                    'action' => 'index',
                    'inherit' => 0,
                    'edit_based' => 0,
                    'name' => 'Show System Settings',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 9,
                    'action' => 'update',
                    'inherit' => 0,
                    'edit_based' => 0,
                    'name' => 'Updates System Settings',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 9,
                    'action' => 'search',
                    'inherit' => 35,
                    'edit_based' => 0,
                    'name' => 'Rebuild Search Indexes',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 9,
                    'action' => 'validate-db',
                    'inherit' => 35,
                    'edit_based' => 0,
                    'name' => 'Validate Database',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 9,
                    'action' => 'wp-login',
                    'inherit' => 33,
                    'edit_based' => 0,
                    'name' => 'WordPress Auto Login Script',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 10,
                    'action' => 'index',
                    'inherit' => 0,
                    'edit_based' => 0,
                    'name' => 'View User List',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 10,
                    'action' => 'edit',
                    'inherit' => 0,
                    'edit_based' => 0,
                    'name' => 'Edit Users',
                    'about' => 'can edit roles of users (restricted by admin level)',
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 10,
                    'action' => 'add',
                    'inherit' => 40,
                    'edit_based' => 0,
                    'name' => 'Add Users',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 10,
                    'action' => 'delete',
                    'inherit' => 40,
                    'edit_based' => 0,
                    'name' => 'Remove Users',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 11,
                    'action' => 'index',
                    'inherit' => 0,
                    'edit_based' => 0,
                    'name' => 'Role Management',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 11,
                    'action' => 'add',
                    'inherit' => 43,
                    'edit_based' => 0,
                    'name' => 'Add Roles',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 11,
                    'action' => 'edit',
                    'inherit' => 43,
                    'edit_based' => 0,
                    'name' => 'Edit Roles',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 11,
                    'action' => 'delete',
                    'inherit' => 43,
                    'edit_based' => 0,
                    'name' => 'Delete Roles',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 11,
                    'action' => 'actions',
                    'inherit' => 43,
                    'edit_based' => 0,
                    'name' => 'Ajax Get Role Actions',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 11,
                    'action' => 'pages',
                    'inherit' => 43,
                    'edit_based' => 0,
                    'name' => 'Set Per Page Actions',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 12,
                    'action' => 'restore',
                    'inherit' => 0,
                    'edit_based' => 0,
                    'name' => 'Restore Deleted Item From Any User',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 12,
                    'action' => 'undo',
                    'inherit' => -1,
                    'edit_based' => 0,
                    'name' => 'Undo Own Actions',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 13,
                    'action' => 'index',
                    'inherit' => -1,
                    'edit_based' => 0,
                    'name' => 'Create Repeater Row',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 14,
                    'action' => 'list',
                    'inherit' => 0,
                    'edit_based' => 1,
                    'name' => 'Gallery List',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 14,
                    'action' => 'edit',
                    'inherit' => 0,
                    'edit_based' => 1,
                    'name' => 'Edit Galleries',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 14,
                    'action' => 'update',
                    'inherit' => 0,
                    'edit_based' => 1,
                    'name' => 'Run Gallery Upload Manager',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 14,
                    'action' => 'sort',
                    'inherit' => 0,
                    'edit_based' => 1,
                    'name' => 'Sort Images',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 14,
                    'action' => 'upload',
                    'inherit' => 0,
                    'edit_based' => 1,
                    'name' => 'Upload Images',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 14,
                    'action' => 'delete',
                    'inherit' => 0,
                    'edit_based' => 1,
                    'name' => 'Delete Images',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 14,
                    'action' => 'caption',
                    'inherit' => 0,
                    'edit_based' => 1,
                    'name' => 'Edit Captions',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 15,
                    'action' => 'list',
                    'inherit' => 0,
                    'edit_based' => 1,
                    'name' => 'Forms List',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 15,
                    'action' => 'submissions',
                    'inherit' => 0,
                    'edit_based' => 1,
                    'name' => 'View Form Submissions',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 15,
                    'action' => 'csv',
                    'inherit' => 0,
                    'edit_based' => 1,
                    'name' => 'Export Form Submissions',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 16,
                    'action' => 'index',
                    'inherit' => 0,
                    'edit_based' => 0,
                    'name' => 'Show Theme Management',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 16,
                    'action' => 'update',
                    'inherit' => 62,
                    'edit_based' => 0,
                    'name' => 'Theme Block Updater',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 16,
                    'action' => 'beacons',
                    'inherit' => 0,
                    'edit_based' => 0,
                    'name' => 'Import Beacons',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 16,
                    'action' => 'beacons-update',
                    'inherit' => 0,
                    'edit_based' => 0,
                    'name' => 'Update Beacon Blocks',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 9,
                    'action' => 'keys',
                    'inherit' => -1,
                    'edit_based' => 0,
                    'name' => 'Request browser API keys',
                    'about' => 'only keys with the string browser in can be called',
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 8,
                    'action' => 'page-state',
                    'inherit' => -1,
                    'edit_based' => 0,
                    'name' => 'Save page list state',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 8,
                    'action' => 'language',
                    'inherit' => -1,
                    'edit_based' => 0,
                    'name' => 'Change current language',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                ),
                array(
                    'controller_id' => 9,
                    'action' => 'upgrade',
                    'inherit' => 0,
                    'edit_based' => 0,
                    'name' => 'Upgrade CMS',
                    'about' => null,
                    'created_at' => $date,
                    'updated_at' => $date
                )
            ));

    }

    /**
     * Revert the changes to the database.
     *
     * @return void
     */
    public function down()
    {

    }

}