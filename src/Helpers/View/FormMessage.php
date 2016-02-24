<?php namespace CoasterCms\Helpers\View;

use Illuminate\Support\MessageBag;

class FormMessage
{

    private static $messages;
    private static $class = array();

    public static function set_class($key, $class)
    {
        self::$class[$key] = $class;
    }

    public static function set($messages)
    {
        self::$messages = $messages;
    }

    public static function add($key, $message)
    {
        if (!is_a(self::$messages, 'Illuminate\Support\MessageBag')) {
            self::$messages = new MessageBag;
        }
        self::$messages->add($key, $message);
    }

    public static function get_message($key)
    {
        if (is_a(self::$messages, 'Illuminate\Support\MessageBag')) {
            $msg = self::$messages->first($key);
            if (!empty($msg)) {
                return $msg;
            } else {
                $alt_key = str_replace(array('[', ']'), array('.', ''), $key);
                return self::$messages->first($alt_key);
            }
        }
        return null;
    }

    public static function get_class($key)
    {
        if (is_a(self::$messages, 'Illuminate\Support\MessageBag')) {
            $alt_key = str_replace(array('[', ']'), array('.', ''), $key);
            if (self::$messages->has($key) || self::$messages->has($alt_key)) {
                return self::$class['error'];
            }
        }
        return null;
    }

}