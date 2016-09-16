<?php namespace CoasterCms\Libraries\Blocks;

use CoasterCms\Libraries\Builder\PageBuilder;
use Request;
use URL;
use View;

class Image extends String_
{

    public function display($content, $options = [])
    {
        $imageData = $this->_defaultData($content);

        if (empty($imageData->file)) {
            return '';
        }

        if (empty($imageData->title)) {
            if (empty($options['title'])) {
                $fileName = substr(strrchr($imageData->file, '/'), 1);
                $imageData->title = str_replace('_', ' ', preg_replace('/\\.[^.\\s]{3,4}$/', '', $fileName));
            } else {
                $imageData->title = $options['title'];
            }
        }

        $imageData->extra_attrs = '';
        $ignoreAttributes = ['height', 'width', 'group', 'view', 'title', 'croppaOptions', 'version'];
        foreach ($options as $option => $val) {
            if (!in_array($option, $ignoreAttributes)) {
                $imageData->extra_attrs .= $option . '="' . $val . '" ';
            }
        }

        $imageData->group = !empty($options['group']) ? $options['group'] : '';
        $imageData->original = URL::to($imageData->file);

        $height = !empty($options['height']) ? $options['height'] : null;
        $width = !empty($options['width']) ? $options['width'] : null;
        if ($height || $width) {
            $croppaOptions = !empty($options['croppaOptions']) ? $options['croppaOptions'] : [];
            $imageData->file = str_replace(URL::to('/'), '', $imageData->file);
            $imageData->file = \Croppa::url($imageData->file, $width, $height, $croppaOptions);
        } else {
            $imageData->file = $imageData->original;
        }

        $template = !empty($options['view']) ? $options['view'] : 'default';
        $imageViews = 'themes.' . PageBuilder::getData('theme') . '.blocks.images.';
        if (View::exists($imageViews . $template)) {
            return View::make($imageViews . $template, array('image' => $imageData))->render();
        } else {
            return 'Image template not found';
        }
    }

    public function edit($content)
    {
        $imageData = $this->_defaultData($content);
        $imageData->file = str_replace(URL::to('/'), '', $imageData->file);
        return parent::edit($imageData);
    }

    public function save($content)
    {
        if (!empty($content['alt']) || !empty($content['source'])) {
            $imageData = new \stdClass;
            $imageData->title = !empty($content['alt']) ? $content['alt'] : '';
            $imageData->file = !empty($content['source']) ? $content['source'] : '';
        } else {
            $imageData = '';
        }
        return parent::save($imageData ? serialize($imageData) : '');
    }

    protected function _defaultData($content)
    {
        $content = @unserialize($content);
        if (empty($content) || !is_a($content, \stdClass::class)) {
            $content = new \stdClass;
        }
        $content->file = empty($content->file) ? '' : $content->file;
        $content->title = empty($content->title) ? '' : $content->title;
        return $content;
    }

    public static function exportFiles($block, $block_data)
    {
        $images= [];
        if (!empty($block_data)) {
            $image_data = unserialize($block_data);
            if (!empty($image_data)) {
                $imageFile = str_replace(URL::to('/'), '', $image_data->file);
                if (!empty($imageFile)) {
                    $images[] = $imageFile;
                }
            }
        }
        return $images;
    }

}