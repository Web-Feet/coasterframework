<?php

use CoasterCms\Models\Page;
use CoasterCms\Models\PageLang;
use CoasterCms\Tests\Feature\Traits\PagesTrait;
use CoasterCms\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithoutMiddleware;

class DisplayPagesTest extends TestCase
{

	use PagesTrait;

    /** @test 
    * @codingStandardsIgnoreLine */
    function can_access_a_live_page_saved_in_the_admin()
    {
        $template = $this->createTemplateView('home');
        $p = $this->createPage('Home', [
        	'template' => $template->id,
        	'live' => 1,
        ], ['url' => 'pageurl']);

        $this->response = $this->get('pageurl');

        $this->response->assertStatus(200);
    }

    /** @test 
    * @codingStandardsIgnoreLine */
    function cannot_access_a_non_live_page_saved_in_the_admin()
    {
        $template = $this->createTemplateView('home');
        $p = $this->createPage('Home', [
        	'template' => $template->id,
        	'live' => 0,
        ]);

        $this->response = $this->get('/');

        $this->response->assertStatus(404);
    }
}