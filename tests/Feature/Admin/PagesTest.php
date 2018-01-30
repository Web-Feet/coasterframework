<?php

use CoasterCms\Models\AdminAction;
use CoasterCms\Models\AdminController;
use CoasterCms\Models\User;
use CoasterCms\Models\UserRole;
use CoasterCms\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithoutMiddleware;

class PagesTest extends TestCase
{
	/** @test */
	function superadmin_user_can_access_pages_view()
	{
		$role = factory(UserRole::class)->states('superadmin')->create(['name' => 'superadmin']);
        $user = factory(User::class)->create(['email' => 'dan@coastercms.org',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
        ]);

	    $this->response = $this->actingAs($user)->get('admin/pages');

	    $this->response->assertStatus(200);
	}

	/** @test */
	function admin_user_can_access_pages_view_if_role_permits_it()
	{
		$role = factory(UserRole::class)->states('admin')->create(['name' => 'admin']);
        $user = factory(User::class)->create(['email' => 'dan@coastercms.org',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
        ]);

        // Allow user role to see pages index action
        $adminController = AdminController::where('controller', 'pages')->first();
        $adminAction = AdminAction::where('controller_id', $adminController->id)->where('action', 'index')->first();
        $role->actions()->sync([$adminAction->id]);

	    $this->response = $this->actingAs($user)->get('admin/pages');

	    $this->response->assertStatus(200);
	}


	/** @test */
	function admin_user_cannot_access_pages_view_if_role_does_not_permit_it()
	{
		$role = factory(UserRole::class)->states('admin')->create(['name' => 'admin']);
        $user = factory(User::class)->create(['email' => 'dan@coastercms.org',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
        ]);

	    $this->response = $this->withExceptionHandling()->actingAs($user)->get('admin/pages');

	    $this->response->assertStatus(403);
	}

	
}