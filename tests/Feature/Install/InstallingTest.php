<?php


use CoasterCms\Facades\Install;
use CoasterCms\Helpers\Install as InstallContract;
use CoasterCms\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Facades\Storage;

class InstallingTest extends TestCase
{
    public function setUp()
    {
        $this->skipInstall = false;
        parent::setUp();
        $this->app->alias(Install::class, InstallContract::class);
        Storage::disk('local')->deleteDirectory('coaster');
    }

    /**
     * @test
     * @codingStandardsIgnoreLine */
    function when_coaster_is_not_installed_accessing_site_redirects_to_install_path()
    {
        Storage::disk('local')->deleteDirectory('coaster');
        $this->response = $this->get('/');

        $this->response->assertStatus(302);
        $this->assertEquals(env('APP_URL') . '/install/permissions', $this->response->getTargetUrl());
    }

    /**
     * @test
     * @codingStandardsIgnoreLine */
    function after_permissions_are_set_redirect_goes_to_set_up_database()
    {
        Install::setInstallState('coaster.install.permissions');
        $this->response = $this->get('/');

        $this->response->assertStatus(302);

        $this->assertEquals(env('APP_URL') . '/install/database', $this->response->getTargetUrl());
    }
}
