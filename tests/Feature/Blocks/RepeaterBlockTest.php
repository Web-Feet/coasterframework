<?php

use CoasterCms\Libraries\Blocks\Repeater;
use CoasterCms\Models\Block;
use CoasterCms\Models\BlockRepeater;
use CoasterCms\Tests\Feature\Blocks\Traits\BlockTrait;
use CoasterCms\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithoutMiddleware;

class RepeaterBlockTest extends TestCase
{
    use BlockTrait;

    /**
     * @test
     * @codingStandardsIgnoreLine */
    function display_renders_a_string()
    {
        $block1 = factory(Block::class)->create(['id' => 1, 'type' => 'string', 'name' => 'string_1']);
        $block2 = factory(Block::class)->create(['id' => 2, 'type' => 'string', 'name' => 'string_2']);
        $repeater = factory(Block::class)->create(['id' => 3, 'type' => 'repeater', 'name' => 'repeater']);

        $blockRepeater = factory(BlockRepeater::class)->create(['id' => 1, 'block_id' => 3, 'blocks' => '1,2']);

        $block = new Block;
        $blockClass = new Repeater($block);

        // @TODO Create repater rows with data

        // Create block view
        $this->createView('repeater', '{{ PageBuilder::block(\'string_1\') }} - {{ PageBuilder::block(\'string_2\') }}');

        $this->assertEquals('A string 1 - A string 2', $blockClass->display(1, ['version' => -1]));
    }
}
