<?php
namespace CoasterCms\Facades;

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\MessageBag;

/**
 * @method static MessageBag defaultBag(string $bag = null, MessageBag|array $messages = null)
 * @method static void set(MessageBag|array $messages, bool $flash = true)
 * @method static void add(string $key, string $message, bool $flash = true)
 * @method static void setErrorClass($errorClass)
 * @method static string get(string $errorClass)
 * @method static string getClass(string $errorClass)
 * @method static string getMessageBag(string $errorClass)
 * @method static string getErrorMessage(string $key)
 * @method static string getErrorClass(string $key)
 */
class FormMessage extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'formMessage';
    }
}
