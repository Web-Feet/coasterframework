<?php namespace CoasterCms\Libraries\Builder;

use Illuminate\Support\MessageBag;

class FormMessage
{

    /**
     * @var MessageBag
     */
    protected static $_messages;

    /**
     * @var string
     */
    protected static $_errorClass;

    /**
     * @param MessageBag|array $messages
     */
    public static function set($messages)
    {
        if (is_array($messages)) {
            self::$_messages = new MessageBag($messages);
        } elseif (is_a($messages, MessageBag::class)) {
            self::$_messages = $messages;
        }
    }

    /**
     * @param string $key
     * @param string $message
     */
    public static function add($key, $message)
    {
        if (!isset(self::$_messages)) {
            self::$_messages = new MessageBag;
        }
        self::$_messages->add($key, $message);
    }

    /**
     * @param string $class
     */
    public static function setErrorClass($class)
    {
        self::$_errorClass = $class;
    }

    /**
     * @param string $key
     * @return string
     */
    public static function getErrorMessage($key)
    {
        if (isset(self::$_messages)) {
            $message = self::$_messages->first($key);
            $message = $message ?: self::$_messages->first(self::_dotNotation($key));
            return $message;
        }
        return '';
    }

    /**
     * @param string $key
     * @return string
     */
    public static function getErrorClass($key)
    {
        if (isset(self::$_messages)) {
            $hasErrors = self::$_messages->has($key);
            $hasErrors = $hasErrors ?: self::$_messages->has(self::_dotNotation($key));
            if ($hasErrors) {
                return isset(self::$_errorClass) ? self::$_errorClass : config('coaster::frontend.form_error_class');
            }
        }
        return '';
    }

    /**
     * @return MessageBag
     */
    public function getMessageBag()
    {
        if (!isset(self::$_messages)) {
            self::$_messages = new MessageBag;
        }
        return static::$_messages;
    }

    /**
     * @param string $key
     * @return string
     */
    protected static function _dotNotation($key)
    {
        return str_replace(['[', ']'], ['.', ''], $key);
    }

    /**
     * @param string $key
     * @return string
     * @deprecated
     */
    public static function get_class($key)
    {
        return self::getErrorClass($key);
    }

    /**
     * @param string $key
     * @return string
     * @deprecated
     */
    public static function get_message($key)
    {
        return self::getErrorMessage($key);
    }

}