<?php

use CoasterCms\Libraries\Blocks\Image;
use CoasterCms\Models\Block;

use \CoasterCms\Tests\Feature\Blocks\Traits\BlockTrait;
use \CoasterCms\Tests\TestCase;

class ImageBlockTest extends TestCase
{
    use BlockTrait;

    public function generateImageObject($data = [])
    {
        $this->imageObj = new \stdClass;
        $this->imageObj->file = 'image.src';
        $this->imageObj->title = 'title';
        $this->imageObj->caption = 'caption';

        foreach ($data as $key => $value) {
            $this->imageObj->{$key} = $value;
        }
        return $this->imageObj;
    }

    /**
     * @test
     * @codingStandardsIgnoreLine */
    public function display_renders_a_string()
    {
        // Create block view
        $this->createView('images', '{{ $image->file }}.{{ $image->caption }}');

        Block::unguard();
        $block = new Block;
        $content = serialize($this->generateImageObject(['file' => 'image-test.src',
            'caption' => 'A caption'
        ]));
        $blockClass = new Image($block);

        $rendered = $blockClass->display($content);
        $this->assertContains('image-test.src', $rendered);
        $this->assertContains('A caption', $rendered);
    }

    /** @test */
    function display_returns_empty_string_if_no_file_is_present()
    {
        // Create block view
        $this->createView('images', '{{ $image->file }}.{{ $image->caption }}');

        $block = factory(Block::class)->create([
            'name' => 'image_test',
            'label' => 'Image test',
            'type' => 'image',
        ]);

        $blockClass = new Image($block);

        $this->assertSame('', $blockClass->display($this->generateImageObject(['file' => ''])));
    }

    /** @test */
    function display_returns_an_error_when_no_view_is_present()
    {
        $block = factory(Block::class)->create([
            'name' => 'image_test',
            'label' => 'Image test',
            'type' => 'image',
        ]);

        $blockClass = new Image($block);
        $content = serialize($this->generateImageObject());
        $this->assertViewNotFound($blockClass->display($content), $block->name, $block->type);
    }

    /**
     * @test
     * @codingStandardsIgnoreLine */
    function data_returns_object()
    {
        Block::unguard();
        $block = new Block;

        $content = serialize($this->generateImageObject(['file' => 'image-test.src',
            'caption' => 'A caption'
        ]));

        $blockClass = new Image($block);

        $returnedData = $blockClass->data($content);
        $this->assertEquals('image-test.src', $returnedData->file);
        $this->assertEquals('A caption', $returnedData->caption);
    }

    /**
     * @test
     * @codingStandardsIgnoreLine */
    function can_get_block_as_json_data()
    {
        Block::unguard();
        $block = new Block;
        $block->name = 'Block name';
        $blockClass = new Image($block);

        $content = serialize($this->generateImageObject());
        $this->assertEquals('{"Block name":{"block":{"name":"Block name"},"data":{"file":"image.src","title":"title","caption":"caption"}}}', $blockClass->toJson($content));
    }
}
