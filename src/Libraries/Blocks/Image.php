<?php namespace CoasterCms\Libraries\Blocks;

use CoasterCms\Libraries\BlockManager;
use CoasterCms\Libraries\Builder\PageBuilder;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;

class Image extends _Base
{

    public static $blocks_key = 'image';

    public static function display($block, $block_data, $options = array())
    {
        if (!empty($block_data)) {
            if (is_string($block_data)) {
                $image_data = unserialize($block_data);
            } else
                $image_data = $block_data;
        }
        if (empty($block_data) || empty($image_data->file)) {
            return '';
        }
        if (empty($image_data->title)) {
            if (empty($options['title'])) {
                // set title to filename
                $image_data->title = substr(strrchr($image_data->file, "/"), 1);
                $image_data->title = str_replace('_', ' ', preg_replace("/\\.[^.\\s]{3,4}$/", "", $image_data->title));
            } else {
                $image_data->title = $options['title'];
            }
        }
        $image_data->extra_attrs = '';

        $not_extra_attrs = array('height', 'width', 'group', 'view', 'title', 'croppaOptions', 'version');

        if (!empty($options)) {
            foreach ($options as $option => $val) {
                if (!in_array($option, $not_extra_attrs)) {
                    $image_data->extra_attrs .= $option . '="' . $val . '" ';
                }
            }
        }
        $image_data->group = '';
        if (!empty($options['group'])) {
            $image_data->group = $options['group'];
        }
        $image_data->original = URL::to($image_data->file);
        $height = !empty($options['height']) ? $options['height'] : null;
        $width = !empty($options['width']) ? $options['width'] : null;
        $croppaOptions = !empty($options['croppaOptions']) ? $options['croppaOptions'] : array();
        if ((!empty($height) || !empty($width)) && !empty($image_data->file))
            $image_data->file = \Croppa::url($image_data->file, $width, $height, $croppaOptions);
        else
            $image_data->file = $image_data->original;
        $template = !empty($options['view']) ? $options['view'] : 'default';
        if (!View::exists('themes.' . PageBuilder::$theme . '.blocks.images.' . $template)) {
            return 'Image template not found';
        } else {
            return View::make('themes.' . PageBuilder::$theme . '.blocks.images.' . $template, array('image' => $image_data))->render();
        }
    }

    public static function edit($block, $block_data, $page_id = 0, $parent_repeater = null)
    {
        $image_data = new \stdClass;
        if (!empty($block_data)) {
            try {
                $image_data = unserialize($block_data);
            } catch (\Exception $e) {
                $image_data->file = '';
                $image_data->title = '';
            }
        } else {
            $image_data->file = '';
            $image_data->title = '';
        }
        self::$edit_id = array($block->id);
        return $image_data;
    }

    public static function submit($page_id, $blocks_key, $repeater_info = null)
    {
        $image_blocks = Request::input($blocks_key);
        if (!empty($image_blocks)) {
            // loop through images to upload
            foreach ($image_blocks as $block_id => $image_data) {
                $image = new \stdClass;
                $image->title = $image_data['alt'];
                $image->file = $image_data['source'];
                BlockManager::update_block($block_id, serialize($image), $page_id, $repeater_info);
            }
        }
    }

}