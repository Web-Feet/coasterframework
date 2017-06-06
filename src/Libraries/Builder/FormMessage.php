<?php namespace CoasterCms\Libraries\Builder;

class FormMessage
{

    /**
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        return ($fm = app('formMessage')) ? call_user_func_array([$fm, $name], $arguments) : null;
    }

}