<?php namespace CoasterCms\Libraries\Blocks;

use CoasterCms\Helpers\Cms\StringHelper;
use CoasterCms\Helpers\Cms\View\CmsBlockInput;
use CoasterCms\Libraries\Builder\PageBuilder;
use CoasterCms\Models\Block;
use CoasterCms\Models\Language;
use CoasterCms\Models\PageBlock;
use CoasterCms\Models\PageBlockDefault;
use CoasterCms\Models\PageBlockRepeaterData;
use CoasterCms\Models\PageSearchData;
use Request;

class String_
{

    protected $_block;

    protected $_pageId;
    protected $_repeaterId;
    protected $_repeaterRowId;

    protected $_editClass;
    protected $_editKeys;
    protected $_editExtraViewData;

    public function __construct(Block $block)
    {
        $this->_block = $block;
        $this->_editClass = strtolower(__CLASS__);
        $this->_editKeys = [$this->_block->id];
        $this->_editExtraViewData = [];

        $this->_pageId = 0;
        $this->_repeaterId = 0;
        $this->_repeaterRowId = 0;
    }

    public function setPageId($pageId)
    {
        $this->_pageId = $pageId;
        return $this;
    }

    public function setRepeaterData($repeaterId, $rowId)
    {
        $this->_repeaterId = $repeaterId;
        $this->_repeaterRowId = $rowId;
        return $this;
    }

    public function display($content, $options = [])
    {
        if (!empty($options['pageBuilder'])) {
            $content = preg_replace_callback(
                '/{{\s*\$(?P<block>\w*)\s*}}/',
                function ($matches) {
                    return str_replace('"', "'", strip_tags(PageBuilder::block($matches['block'])));
                },
                $content
            );
            $content = str_replace('%page_name%', PageBuilder::pageName(), $content);
            $content = str_replace('%site_name%', config('coaster::site.name'), $content);
        }
        if (!empty($options['meta'])) {
            $content = trim(str_replace(PHP_EOL, ' ', $content));
            $content = preg_replace('/\s+/', ' ', $content);
            $content = htmlentities(strip_tags(html_entity_decode($content, ENT_QUOTES, 'UTF-8')));
            $content = StringHelper::cutString($content);
        }
        return $content;
    }

    public function submission($formData)
    {

    }

    public function edit($content)
    {
        return CmsBlockInput::make($this->_block->type, [
                'label' => $this->_block->label,
                'name' => $this->_getInputHTMLKey(),
                'content' => $content,
                'note' => $this->_block->note,
                '_block' => $this->_block,
                '_pageId' => $this->_pageId,
                '_repeaterId' => $this->_repeaterId,
                '_repeaterRowId' => $this->_repeaterRowId,
                '_editKeys' => $this->_editKeys
            ] + $this->_editExtraViewData);
    }

    public function submit($postDataKey = '')
    {
        if ($updated_text_blocks = Request::input($postDataKey . $this->_editClass)) {
            foreach ($updated_text_blocks as $blockId => $content) {
                $this->_block->id = $blockId;
                $this->save($content);
            }
        }
    }

    public function save($content)
    {
        $this->_save($content ?: '');
    }

    public function publish()
    {

    }

    protected function _save($content)
    {
        if ($this->_pageId) {
            if ($this->_repeaterId && $this->_repeaterRowId) {
                $tmp = new \stdClass;
                $tmp->repeater_id = $this->_repeaterId;
                $tmp->row_id = $this->_repeaterRowId;
                PageBlockRepeaterData::update_block($this->_block->id, $content, $this->_pageId, $tmp);
            } else {
                PageBlock::update_block($this->_block->id, $content, $this->_pageId);
            }
            PageSearchData::update_text($this->_block->id, $content, $this->_pageId, Language::current());
        } else {
            PageBlockDefault::update_block($this->_block->id, $content);
        }
    }

    protected function _getInputHTMLKey()
    {
        if ($this->_repeaterId && $this->_repeaterRowId) {
            $inputHMTLKey = 'repeater[' . $this->_repeaterId . '][' . $this->_repeaterRowId . '][' . $this->_editClass . ']';
        } else {
            $inputHMTLKey = $this->_editClass;
        }
        foreach ($this->_editKeys as $key) {
            $inputHMTLKey .= '[' . $key . ']';
        }
        return $inputHMTLKey;
    }


    public function search_text($content)
    {
        $data = @unserialize($content);
        if ($content === 'b:0;' || $data !== false) {
            return null; // serialized data should have custom function
        } else {
            return strip_tags($content);
        }
    }

    public function filter($search, $type)
    {
        $live_blocks = PageBlock::page_blocks_on_live_page_versions($this->_block->id);
        $page_ids = array();
        if (!empty($live_blocks)) {
            foreach ($live_blocks as $live_block) {
                switch ($type) {
                    case '=':
                        if ($live_block->content == $search) {
                            $page_ids[] = $live_block->page_id;
                        }
                        break;
                    case 'in':
                        if (strpos($live_block->content, $search) !== false) {
                            $page_ids[] = $live_block->page_id;
                        }
                        break;
                }
            }
        }
        return $page_ids;
    }

    /**
     * @param \CoasterCms\Models\Block $block
     * @param string $block_data
     * @return array
     */
    public static function exportFiles($block, $block_data)
    {
        return [];
    }

    /**
     * @return array
     */
    public static function block_settings_action()
    {
        return ['action' => '', 'name' => ''];
    }

}
