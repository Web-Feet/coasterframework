<?php

use CoasterCms\Libraries\Blocks\Image;
use CoasterCms\Models\Block;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithoutMiddleware;

use \Tests\Feature\Blocks\Traits\BlockTrait;
use \Tests\TestCase;

class ImageBlockTest extends TestCase
{
    use BlockTrait;

    public function generateImageObject($data = [])
    {
        $this->imageObj = new \stdClass;
        $this->imageObj->file = 'image.src';
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
        $block->content = serialize($this->generateImageObject(['file' => 'image-test.src',
            'caption' => 'A caption'
        ]));
        $blockClass = new Image($block);

        $rendered = $blockClass->display($block->content);
        $this->assertContains('image-test.src', $rendered);
        $this->assertContains('A caption', $rendered);
    }

    /**
     * @test
     * @codingStandardsIgnoreLine */
    function data_returns_object()
    {
        Block::unguard();
        $block = new Block;

        $block->content = serialize($this->generateImageObject(['file' => 'image-test.src',
            'caption' => 'A caption'
        ]));

        $blockClass = new Image($block);

        $returnedData = $blockClass->data($block->content);
        $this->assertEquals('image-test.src', $returnedData->file);
        $this->assertEquals('A caption', $returnedData->caption);
    }
}
