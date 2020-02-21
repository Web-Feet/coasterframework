<?php

use CoasterCms\Libraries\Blocks\Repeater;
use CoasterCms\Models\Block;
use CoasterCms\Models\BlockRepeater;
use CoasterCms\Models\PageBlockRepeaterData;
use CoasterCms\Models\PageBlockRepeaterRows;
use CoasterCms\Tests\Feature\Blocks\Traits\BlockTrait;
use CoasterCms\Tests\TestCase;

class RepeaterBlockTest extends TestCase
{
  use BlockTrait;

  /**
   * @test
   * @codingStandardsIgnoreLine */
  function display_renders_a_string()
  {
    $block = factory(Block::class)->create(['id' => 1, 'type' => 'string', 'name' => 'a_string']);
    // $block2 = factory(Block::class)->create(['id' => 2, 'type' => 'string', 'name' => 'string_2']);
    $repeater = factory(Block::class)->create(['id' => 3, 'type' => 'repeater', 'name' => 'repeater']);

    $blockRepeater = factory(BlockRepeater::class)->create(['id' => 1, 'block_id' => 3, 'blocks' => '1']);

    $blockClass = new Repeater($repeater);

    // Create repater rows
    $repeaterRow1 = factory(PageBlockRepeaterRows::class)->create([
      'repeater_id' => $blockRepeater->id,
      'row_id' => 1,
    ]);
    $repeaterRow2 = factory(PageBlockRepeaterRows::class)->create([
      'repeater_id' => $blockRepeater->id,
      'row_id' => 2,
    ]);

    // Create repater row data
    factory(PageBlockRepeaterData::class)->create([
      'row_key' => $repeaterRow1->row_id,
      'block_id' => 0,
      'version' => 1,
      'content' => 0,
    ]);
    factory(PageBlockRepeaterData::class)->create([
      'row_key' => $repeaterRow1->row_id,
      'block_id' => $block->id,
      'version' => 1,
      'content' => 'A string 1',
    ]);

    factory(PageBlockRepeaterData::class)->create([
      'row_key' => $repeaterRow1->row_id,
      'block_id' => 0,
      'version' => 1,
      'content' => 1,
    ]);
    factory(PageBlockRepeaterData::class)->create([
      'row_key' => $repeaterRow2->row_id,
      'block_id' => $block->id,
      'version' => 1,
      'content' => 'A string 2',
    ]);

    // Create block view
    $this->createView('repeater', '{{ PageBuilder::block(\'a_string\') }} - ');

    $this->assertEquals('A string 1 - A string 2 - ', $blockClass->display($blockRepeater->id, ['version' => -1]));
  }

  /**
   * @test
   * @codingStandardsIgnoreLine */
  function can_display_repeater_with_multiple_blocks()
  {
    $block1 = factory(Block::class)->create(['id' => 1, 'type' => 'string', 'name' => 'string_4']);
    $block2 = factory(Block::class)->create(['id' => 2, 'type' => 'string', 'name' => 'string_5']);
    $repeater = factory(Block::class)->create(['id' => 3, 'type' => 'repeater', 'name' => 'repeater']);

    $blockRepeater = factory(BlockRepeater::class)->create(['id' => 1, 'block_id' => 3, 'blocks' => '1,2']);

    $blockClass = new Repeater($repeater);

    // Create repater rows
    $repeaterRow1 = factory(PageBlockRepeaterRows::class)->create([
      'repeater_id' => $blockRepeater->id,
      'row_id' => 1,
    ]);
    $repeaterRow2 = factory(PageBlockRepeaterRows::class)->create([
      'repeater_id' => $blockRepeater->id,
      'row_id' => 2,
    ]);

    // Create repater row data
    // First item
    factory(PageBlockRepeaterData::class)->create([
      'row_key' => $repeaterRow1->row_id,
      'block_id' => 0,
      'version' => 1,
      'content' => 0,
    ]);
    factory(PageBlockRepeaterData::class)->create([
      'row_key' => $repeaterRow1->row_id,
      'block_id' => $block1->id,
      'version' => 1,
      'content' => 'A string 1a',
    ]);
    factory(PageBlockRepeaterData::class)->create([
      'row_key' => $repeaterRow1->row_id,
      'block_id' => $block2->id,
      'version' => 1,
      'content' => 'A string 1b',
    ]);

    // Second item
    factory(PageBlockRepeaterData::class)->create([
      'row_key' => $repeaterRow2->row_id,
      'block_id' => 0,
      'version' => 1,
      'content' => 1,
    ]);
    factory(PageBlockRepeaterData::class)->create([
      'row_key' => $repeaterRow2->row_id,
      'block_id' => $block1->id,
      'version' => 1,
      'content' => 'A string 2a',
    ]);

    factory(PageBlockRepeaterData::class)->create([
      'row_key' => $repeaterRow2->row_id,
      'block_id' => $block2->id,
      'version' => 1,
      'content' => 'A string 2b',
    ]);

    // Create block view
    $this->createView('repeater', '{{ PageBuilder::block(\'string_4\') }}, {{ PageBuilder::block(\'string_5\') }} ');

    $this->assertEquals('A string 1a, A string 1b A string 2a, A string 2b ', $blockClass->display($blockRepeater->id, ['version' => -1]));
  }
}
