<?php namespace CoasterCms\Helpers\Cms\View;

use CoasterCms\Facades\FormMessage;
use CoasterCms\Models\Block;
use Request;
use View;

class CmsBlockInput
{

    public static function getView($type)
    {
        $typeParts = explode('.', $type);
        $typeExtension = '.' . ((count($typeParts) > 1) ? implode('.', array_slice($typeParts, 1)) : 'main');

        $typeClassName = Block::getBlockClass($typeParts[0]);
        $typeClasses = array_merge([$typeClassName], class_parents($typeClassName));

        foreach ($typeClasses as $typeClass) {

            $blockTypeView = Block::getBlockType($typeClass) . $typeExtension;
            $blockTypeView = strtolower($blockTypeView);

            $locations = [
                'coaster.blocks.',
                'coaster::blocks.',
            ];

            foreach ($locations as $location) {
                if (View::exists($location . $blockTypeView)) {
                    return $location . $blockTypeView;
                }
            }

        }

        return null;
    }

    public static function appendName($name, $append)
    {
        if ($pos = strpos($name, '[')) {
            return substr_replace($name, $append, $pos, 0);
        } else {
            return $name.$append;
        }
    }

    public static function make($type, $options = [])
    {
        if (!($view = self::getView($type))) {
            return null;
        }

        if (!empty($options['name'])) {
            $dotName = str_replace(['[', ']'], ['.', ''], $options['name']);
            $options['submitted_data'] = array_key_exists('submitted_data', $options) ? $options['submitted_data'] : Request::input($dotName);
            $options['field_class'] = FormMessage::getErrorClass($options['name']);
            $options['field_message'] = FormMessage::getErrorMessage($options['name']);
        }

        if (isset($options['disabled']) && $options['disabled']) {
            $options['input_attr']['disabled'] = ['disabled' => 'disabled'];
            $options['disabled'] = ['disabled' => 'disabled'];
        } else {
            $options['disabled'] = [];
        }

        $options = array_merge([
            'input_attr' => [],
            'class' => '',
            'content' => '',
            'name' => '',
            'note' => '',
            'label' => 'None set',
            'submitted_data' => '',
            'field_class' => '',
            'field_message' => ''
        ], $options);

        if (!empty($options['value']) && is_string($options['content']) && $options['content'] === '') {
            $options['content'] = $options['value'];
        }

        return View::make($view, $options)->render();
    }

}
