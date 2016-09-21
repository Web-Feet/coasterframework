<?php namespace CoasterCms\Libraries\Blocks;

use Carbon\Carbon;
use CoasterCms\Helpers\Cms\DateTimeHelper;

class Datetime extends String_
{
    /**
     * Extra format options (either php format or coaster presets coaster.short/coaster.long)
     * @param string $content
     * @param array $options
     * @return string
     */
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

    /**
     * Convert content to jQuery datetime picker format
     * @param string $content
     * @return string
     */
    public function edit($content)
    {
        $content = DateTimeHelper::mysqlToJQuery($content);
        return parent::edit($content);
    }

    /**
     * Convert jQuery datetime picker format to mysql
     * @param array $postContent
     * @return static
     */
    public function submit($postContent)
    {
        return $this->save($postContent ? DateTimeHelper::jQueryToMysql($postContent) : '');
    }

    /**
     * Add word representations for month/day so they can also be searched
     * @param null|string $content
     * @return null|string
     */
    public function generateSearchText($content)
    {
        return $content ? (new Carbon($content))->format('d/m/Y l dS F') : null;
    }

    /**
     * Datetime exact match check as well as between check
     * @param string $content
     * @param string $search
     * @param string $type
     * @return bool
     */
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