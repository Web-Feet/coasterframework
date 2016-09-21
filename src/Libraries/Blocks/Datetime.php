<?php namespace CoasterCms\Libraries\Blocks;

use Carbon\Carbon;
use CoasterCms\Helpers\Cms\DateTimeHelper;

class Datetime extends String_
{

    public function display($content, $options = [])
    {
        if (!empty($options['format'])) {
            if (strpos($options['format'], 'coaster.') === 0) {
                $options['format'] = config('coaster:date.format'.substr($options['format'], 8));
            }
            $content = (new Carbon($content))->format($options['format']);
        }
        return $content;
    }

    public function edit($content)
    {
        $content = DateTimeHelper::mysqlToJQuery($content);
        return parent::edit($content);
    }

    public function save($content)
    {
        return parent::save($content ? DateTimeHelper::jQueryToMysql($content) : '');
    }

    public function generateSearchText($content)
    {
        return $content ? (new Carbon($content))->format('d/m/Y l dS F') : null;
    }

    public function filter($content, $search, $type)
    {
        $search = is_array($search) ? $search : [$search];
        $search = array_map(function($searchValue) {return is_a($searchValue, Carbon::class) ? $searchValue : new Carbon($searchValue);}, $search);
        $current = (new Carbon($content));
        switch ($type) {
            case 'in':
                return ($current->gte($search[0]) && $current->lt($search[1]));
                break;
            default:
                return $current->eq($search[0]);
        }
    }

}