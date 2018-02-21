<?php
namespace CoasterCms\Tests\Unit\Pagebuilder;

use CoasterCms\Facades\PageBuilder;
use CoasterCms\Models\Block;
use CoasterCms\Models\PageBlockDefault;
use CoasterCms\Tests\Feature\Blocks\Traits\BlockTrait;
use CoasterCms\Tests\TestCase;

class DefaultPageBuilderTest extends TestCase
{
    use BlockTrait;

    /**
     * @test
     * @codingStandardsIgnoreLine */
    function can_get_and_render_a_global_block_by_name()
    {
        $block = factory(Block::class)->create([
            'name' => 'stringblock',
            'type' => 'string',
        ]);

        PageBlockDefault::unguard();
        PageBlockDefault::create(['language_id' => 1, 'block_id' => $block->id, 'content' => 'content string', 'version' => 0]);

        $this->assertEquals('content string', PageBuilder::block('stringblock'));
    }

    /**
     * @test
     * @codingStandardsIgnoreLine */
    function block_that_does_not_exist_returns_block_not_found_in_development()
    {
        config(['app.env' => 'development']);

        $this->assertEquals('block not found', PageBuilder::block('block_name'));
    }

    /**
     * @test
     * @codingStandardsIgnoreLine */
    function block_that_does_not_exist_returns_empty_string_in_production()
    {
        config(['app.env' => 'production']);

        $this->assertEquals('', PageBuilder::block('a_block'));
    }
}
