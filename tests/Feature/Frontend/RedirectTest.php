<?php

use CoasterCms\Models\PageRedirect;

use CoasterCms\Tests\TestCase;

class RedirectTest extends TestCase
{
    /**
     * @test
     * @codingStandardsIgnoreLine */
    function accessing_a_route_with_a_redirect_on_redirects_to_expected_url()
    {
        PageRedirect::unguard();
        PageRedirect::create(['redirect' => '/from', 'to' => '/to', 'type' => 301]);

        $this->response = $this->get('/from');

        $this->response->assertStatus(301);
        $this->assertEquals(url('/to'), $this->response->getTargetUrl());
    }

    /**
     * @test
     * @codingStandardsIgnoreLine */
    function can_redirect_route_matching_a_wildcard()
    {
        PageRedirect::unguard();
        PageRedirect::create(['redirect' => '/from/%', 'to' => '/to', 'type' => 301]);

        $this->response = $this->get('/from/anything-goes-here');

        $this->response->assertStatus(301);
        $this->assertEquals(url('/to'), $this->response->getTargetUrl());
    }

    /**
     * @test
     * @codingStandardsIgnoreLine */
    function cannot_redirect_route_matching_a_wildcard_where_wildcard_does_not_exist_in_requested_url()
    {
        PageRedirect::unguard();
        PageRedirect::create(['redirect' => '/from/%', 'to' => '/to', 'type' => 301]);

        $this->response = $this->get('/from');

        $this->response->assertStatus(404);
    }
}
