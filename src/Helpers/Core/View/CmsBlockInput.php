<?php namespace CoasterCms\Helpers\Core\View;

use CoasterCms\Libraries\Builder\FormMessage;
use Request;
use View;

class CmsBlockInput
{

    public static function exists($type)
    {
        if (strpos($type, '.') === false) {
            $type = $type . '.main';
        }
        $type = strtolower($type);

        $locations = array(
            'coaster.blocks.',
            'coaster::blocks.',
        );

        foreach ($locations as $location) {
            if (View::exists($location . $type)) {
                return $location;
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

    public static function make($type, $options = array())
    {
        if (!($location = self::exists($type))) {
            return null;
        }
        if (strpos($type, '.') === false) {
            $type = $type . '.main';
        }

        if (!empty($options['name'])) {
            $options['submitted_data'] = Request::input($options['name']);
            $options['field_class'] = FormMessage::getErrorClass($options['name']);
            $options['field_message'] = FormMessage::getErrorMessage($options['name']);
        }

        if (isset($options['disabled']) && $options['disabled']) {
            $options['disabled'] = ['disabled' => 'disabled'];
        } else {
            $options['disabled'] = [];
        }

        $options = array_merge([
            'class' => '',
            'content' => '',
            'name' => '',
            'note' => '',
            'label' => 'None set',
            'submitted_data' => '',
            'form_class' => '',
            'form_message' => ''
        ], $options);

        return View::make($location . strtolower($type), $options)->render();
    }

}