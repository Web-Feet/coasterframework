<?php namespace CoasterCms\Libraries\Blocks;

use Carbon\Carbon;
use CoasterCms\Helpers\Cms\DateTimeHelper;
use CoasterCms\Models\PageBlock;

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
        $this->_save(DateTimeHelper::jQueryToMysql($content));
    }

    public function filter($search, $type)
    {
        $live_blocks = PageBlock::page_blocks_on_live_page_versions($this->_block->id);
        $page_ids = array();
        if (!empty($live_blocks)) {
            $search = is_array($search) ? $search : [$search];
            $search = array_map(function($searchValue) {return is_a($searchValue, Carbon::class) ? $searchValue : new Carbon($searchValue);}, $search);
            foreach ($live_blocks as $live_block) {
                $current = (new Carbon($live_block->content));
                switch ($type) {
                    case '=':
                        if ($current->eq($search[0])) {
                            $page_ids[] = $live_block->page_id;
                        }
                        break;
                    case 'in':
                        if ($current->gte($search[0]) && $current->lt($search[1])) {
                            $page_ids[] = $live_block->page_id;
                        }
                        break;
                }
            }
        }
        return $page_ids;
    }

}