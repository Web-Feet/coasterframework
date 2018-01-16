<?php

use CoasterCms\Libraries\Blocks\String_;
use CoasterCms\Libraries\Builder\PageBuilderFacade;
use CoasterCms\Models\Block;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Mockery as m;
use Tests\TestCase;

class StringBlockTest extends TestCase
{
    /**
     * @test
     * @codingStandardsIgnoreLine */
    public function display_renders_a_string()
    {
        Block::unguard();
        $block = new Block;
        $block->content = 'A string';
        $stringClass = new String_($block);

        $this->assertEquals('A string', $stringClass->display($block->content));
    }

    /**
     * @test
     * @codingStandardsIgnoreLine */
    function data_returns_string()
    {
        Block::unguard();
        $block = new Block;
        $block->content = 'A string';
        $stringClass = new String_($block);

        $this->assertEquals('A string', $stringClass->data($block->content));
    }

    /**
     * @test
     * @codingStandardsIgnoreLine */
    function page_name_can_be_passed_into_string_when_meta_option_is_true()
    {
        PageBuilderFacade::shouldReceive('getData');
        PageBuilderFacade::shouldReceive('pageName')->andReturn('The page');

        Block::unguard();
        $block = new Block;
        $block->content = 'A string %page_name%';
        $stringClass = new String_($block);

        $this->assertEquals('A string The page', $stringClass->display($block->content, ['meta' => true]));
    }

    /**
     * @test
     * @codingStandardsIgnoreLine */
    function site_name_can_be_passed_into_string_when_meta_option_is_true()
    {
        config(['coaster::site.name' => 'The site']);
        PageBuilderFacade::shouldReceive('getData')->shouldReceive('pageName');

        Block::unguard();
        $block = new Block;
        $block->content = 'A string %site_name%';
        $stringClass = new String_($block);

        $this->assertEquals('A string The site', $stringClass->display($block->content, ['meta' => true]));
    }

    /**
     * @test
     * @codingStandardsIgnoreLine */
    function if_meta_strip_tags()
    {
        Block::unguard();
        $block = new Block;
        $block->content = 'A string <h3>In a tag</h3>';
        $stringClass = new String_($block);

        $this->assertEquals('A string In a tag', $stringClass->display($block->content, ['meta' => true]));
    }
}
