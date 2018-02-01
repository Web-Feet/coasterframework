<?php

use CoasterCms\Models\Page;
use CoasterCms\Models\PageLang;
use CoasterCms\Models\User;
use CoasterCms\Models\UserRole;
use CoasterCms\Tests\Feature\Admin\Traits\AdminActions;
use CoasterCms\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithoutMiddleware;

class PagesTest extends TestCase
{
    use AdminActions;

    /**
     * @test
     * @codingStandardsIgnoreLine */
    function admin_user_can_access_pages_add_view_if_role_permits_it()
    {
        $role = factory(UserRole::class)->states('admin')->create(['name' => 'admin']);
        $user = factory(User::class)->create(['email' => 'dan@coastercms.org',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
        ]);

        $this->enableActionAccessForRole($role, 'pages', 'add');

        $this->response = $this->actingAs($user)->get('admin/pages/add');

        $this->response->assertStatus(200);
    }

    /**
     * @test
     * @codingStandardsIgnoreLine */
    function admin_user_cannot_access_pages_add_view_if_role_does_not_permit_it()
    {
        $role = factory(UserRole::class)->states('admin')->create(['name' => 'admin']);
        $user = factory(User::class)->create(['email' => 'dan@coastercms.org',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
        ]);

        $this->response = $this->withExceptionHandling()->actingAs($user)->get('admin/pages/add');

        $this->response->assertStatus(403);
    }

    /**
     * @test
     * @codingStandardsIgnoreLine */
    function can_add_a_root_page()
    {
        $role = factory(UserRole::class)->states('admin')->create(['name' => 'admin']);
        $user = factory(User::class)->create(['email' => 'dan@coastercms.org',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
        ]);

        $this->enableActionAccessForRole($role, 'pages', 'add');

        $this->response = $this->actingAs($user)->post('admin/pages/add', [
            'page_info' => [
                'parent' => 0,
            ],
            'page_info_lang' => [
                'name' => 'A page title',
                'url' => 'a-page-title',
            ],
        ]);

        $this->response->assertStatus(302);

        $this->assertCount(1, Page::all());
        $this->assertCount(1, PageLang::all());

        $pageLang = PageLang::first();

        $this->assertEquals(1, $pageLang->id);
        $this->assertEquals('A page title', $pageLang->name);
        $this->assertEquals('a-page-title', $pageLang->url);
    }
}
