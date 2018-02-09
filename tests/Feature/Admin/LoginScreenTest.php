<?php

// namespace CoasterCms\Tests\Feature\Admin;

use CoasterCms\Models\User;
use CoasterCms\Models\UserRole;
use CoasterCms\Tests\TestCase;

class LoginScreenTest extends TestCase
{
    /**
     * @test
     * @codingStandardsIgnoreLine */
    function can_see_admin_login_page()
    {
        $this->response = $this->withExceptionHandling()->get('/admin/login');

        $this->response->assertStatus(200);
    }


    /**
     * @test
     * @codingStandardsIgnoreLine */
    function cannot_login_with_invalid_credentials()
    {
        factory(User::class)->create(['email' => 'dan@coastercms.org',
            'password' => bcrypt('password'),
            'role_id' => 1,
        ]);

        $this->response = $this->post('/admin/login', ['username' => 'dan@coastercms.org', 'password' => 'invalid password attempt']);

        $this->response->assertStatus(200);
        $this->response->assertSee('Username or password incorrect');
    }

       /**
     * @test
     * @codingStandardsIgnoreLine */
    function cannot_login_if_not_admin()
    {
        $role = factory(UserRole::class)->create(['name' => 'attemptor']);
        factory(User::class)->create(['email' => 'dan@coastercms.org',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
        ]);

        $this->response = $this->post('/admin/login', ['username' => 'dan@coastercms.org', 'password' => 'password']);

        while ($this->response->isRedirect()) {
            $this->response = $this->get($this->response->headers->get('Location'));
        }

        $this->response->assertStatus(200);
        $this->response->assertSee('Username');
    }

     /**
     * @test
     * @codingStandardsIgnoreLine */
    function can_login_if_admin()
    {
        $role = factory(UserRole::class)->states('admin')->create(['name' => 'admin']);
        factory(User::class)->create(['email' => 'dan@coastercms.org',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
        ]);

        $this->response = $this->post('/admin/login', ['username' => 'dan@coastercms.org', 'password' => 'password']);

        $this->response->assertStatus(302);
        $this->assertEquals(env('APP_URL').'/admin/home', $this->response->getTargetUrl());
    }

     /**
     * @test
     * @codingStandardsIgnoreLine */
    function can_login_if_superadmin()
    {
        $role = factory(UserRole::class)->states('superadmin')->create(['name' => 'superadmin']);
        factory(User::class)->create(['email' => 'dan@coastercms.org',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
        ]);

        $this->response = $this->post('/admin/login', ['username' => 'dan@coastercms.org', 'password' => 'password']);

        $this->response->assertStatus(302);
        $this->assertEquals(env('APP_URL').'/admin/home', $this->response->getTargetUrl());
    }
}
