<?php namespace CoasterCms\Libraries\Blocks;

use CoasterCms\Models\PageBlock;

class Selectmultiple extends Select
{

    public function display($content, $options = [])
    {
        $content = $content ? unserialize($content) : [];
        return $this->display($content, $options);
    }

    public function edit($content)
    {
        $content = @unserialize($content);
        return parent::edit($content);
    }

    public function save($content)
    {
        $content['select'] = !empty($content['select']) ? serialize($content['select']) : '';
        return parent::save($content);
    }

    public function generateSearchText($content)
    {
        $content = @unserialize($content) ?: [];
        return $this->_generateSearchText(...$content);
    }

    public function filter($search, $type)
    {
        $live_blocks = PageBlock::page_blocks_on_live_page_versions($this->_block->id);
        $page_ids = array();
        if (!empty($live_blocks) && $search) {
            foreach ($live_blocks as $live_block) {
                $items = !empty($live_block->content) ? unserialize($live_block->content) : array();
                switch ($type) {
                    case '=':
                        if (in_array($search, $items)) {
                            $page_ids[] = $live_block->page_id;
                        }
                        break;
                    case 'in':
                        foreach ($items as $item) {
                            if (strpos($item, $search) !== false) {
                                $page_ids[] = $live_block->page_id;
                            }
                        }
                        break;
                }
            }
        }
        return $page_ids;
    }

}
