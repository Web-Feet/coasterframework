<?php

use CoasterCms\Facades\PageBuilder as PageBuilderFacade;
use CoasterCms\Libraries\Blocks\String_;
use CoasterCms\Models\Block;
use CoasterCms\Tests\Feature\Blocks\Traits\BlockTrait;
use CoasterCms\Tests\TestCase;


class StringBlockTest extends TestCase
{
    use BlockTrait;
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
    function can_display_through_a_view()
    {
        Block::unguard();
        $block = new Block;
        $block->content = 'string';
        $stringClass = new String_($block);

        // Create block view
        $this->createView('strings', 'The {{ $data }}');

        $this->assertEquals('The string', $stringClass->display($block->content, ['view' => 'strings.default']));
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
        PageBuilderFacade::shouldReceive('getData')->once();
        PageBuilderFacade::shouldReceive('pageName')->once()->andReturn('The page');

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
        PageBuilderFacade::shouldReceive('getData')->shouldReceive('pageName')->once();

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
