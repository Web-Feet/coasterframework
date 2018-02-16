<?php

use CoasterCms\Libraries\Blocks\Stringwprice;
use CoasterCms\Models\Block;
use CoasterCms\Tests\Feature\Blocks\Traits\BlockTrait;
use CoasterCms\Tests\TestCase;

class StringWPriceBlockTest extends TestCase
{
    use BlockTrait;

    /** @test */
    function can_unserialze_valid_data_object()
    {
        $object = new stdClass;
        $object->text = 'This is a price';
        $object->price = 10;

        $stringWPrice = new Stringwprice(new Block);

        $resultingObject = $stringWPrice->data(serialize($object));

        $this->assertInstanceOf(stdClass::class, $resultingObject);
        $this->assertSame('This is a price', $resultingObject->text);
        $this->assertSame(10, $resultingObject->price);
    }

    /** @test */
    function empty_content_still_returns_a_valid_object()
    {
        $stringWPrice = new Stringwprice(new Block);

        $resultingObject = $stringWPrice->data(null);

        $this->assertInstanceOf(stdClass::class, $resultingObject);
        $this->assertSame('', $resultingObject->text);
        $this->assertSame('', $resultingObject->price);
    }

    /** @test */
    function display_returns_an_error_when_no_view_is_present()
    {
        $object = new stdClass;
        $object->text = 'This is a price';
        $object->price = 10;

        $block = factory(Block::class)->create([
            'name' => 'stringwprice_test',
            'label' => 'String Price test',
            'type' => 'stringwprice',
        ]);

        $stringWPrice = new Stringwprice($block);
        $this->assertViewNotFound($stringWPrice->display($object), $block->name, $block->type);
    }

    /** @test */
    function display_returns_a_string()
    {
        $object = new stdClass;
        $object->text = 'This is a price';
        $object->price = 10;

        $block = new Block;
        $block->name = 'stringwprice';

    	$stringWPrice = new Stringwprice($block);


        $this->createView($block->type, '{!! $data->text !!} - &pound;{!! $data->price !!}');

    	$this->assertEquals('This is a price - &pound;10', $stringWPrice->display(serialize($object)));
    }

    /**
     * @test
     * @codingStandardsIgnoreLine */
    function can_get_block_as_json_data()
    {
        $object = new stdClass;
        $object->text = 'This is a price';
        $object->price = 10;

        Block::unguard();
        $block = new Block;
        $block->name = 'String with price name';
        $stringWPrice = new Stringwprice($block);

        $this->assertEquals('{"String with price name":{"block":{"name":"String with price name"},"data":{"text":"","price":""}}}', $stringWPrice->toJson($object));
    }
}
