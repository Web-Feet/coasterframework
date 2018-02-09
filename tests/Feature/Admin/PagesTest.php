<?php

use CoasterCms\Models\User;
use CoasterCms\Models\UserRole;
use CoasterCms\Tests\Feature\Admin\Traits\AdminActions;
use CoasterCms\Tests\TestCase;

class PagesTest extends TestCase
{
    use AdminActions;

    /**
     * @test
     * @codingStandardsIgnoreLine */
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

	/**
     * @test
     * @codingStandardsIgnoreLine */
	function admin_user_can_access_pages_view_if_role_permits_it()
    {
    	$role = factory(UserRole::class)->states('admin')->create(['name' => 'admin']);
        $user = factory(User::class)->create(['email' => 'dan@coastercms.org',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
        ]);

        $this->enableActionAccessForRole($role, 'pages', 'index');

        $this->response = $this->actingAs($user)->get('admin/pages');

        $this->response->assertStatus(200);
    }

	/**
     * @test
     * @codingStandardsIgnoreLine */
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
