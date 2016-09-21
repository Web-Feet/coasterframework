<?php namespace CoasterCms\Libraries\Blocks;

use CoasterCms\Helpers\Cms\StringHelper;
use CoasterCms\Helpers\Cms\View\CmsBlockInput;
use CoasterCms\Libraries\Builder\PageBuilder;
use CoasterCms\Models\Block;

class String_
{
    public static $blockSettings = [];

    protected $_block;
    protected $_editViewData;
    protected $_isSaved;
    protected $_contentSaved;

    public function __construct(Block $block)
    {
        $this->_block = $block;
        $this->_editViewData = [];
        $this->_isSaved = false;
        $this->_contentSaved = '';
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
        return null;
    }

    public function edit($content)
    {
        return CmsBlockInput::make($this->_block->type, $this->_editViewData + [
                'label' => $this->_block->label,
                'name' => $this->_getInputHTMLKey(),
                'content' => $content,
                'note' => $this->_block->note,
                '_block' => $this->_block
            ]);
    }

    public function save($content)
    {
        return $this->_save((string) $content);
    }

    public function saveRaw($content)
    {
        return $this->_save((string) $content);
    }

    protected function _save($content)
    {
        $this->_isSaved = true;
        $this->_contentSaved = $content;
        $this->_block->updateContent($content);
        return $this;
    }

    public function getSavedContent()
    {
        return $this->_contentSaved;
    }

    public function publish()
    {
        if ($this->_block->getPageId() && $this->_isSaved) {
            $searchText = $this->generateSearchText($this->_contentSaved);
            $this->_block->publishContent($searchText);
        }
        return $this;
    }

    public function filter($content, $search, $type)
    {
        switch ($type) {
            case 'in':
                return (strpos($content, $search) !== false);
                break;
            default:
                return ($content == $search);
        }
    }

    public function generateSearchText($content)
    {
        return $this->_generateSearchText($content);
    }

    protected function _generateSearchText(...$contentParts)
    {
        $searchText = '';
        foreach ($contentParts as $contentPart) {
            $contentPart = (string) $contentPart;
            if ($contentPart !== '') {
                $searchText .= $contentPart;
            }
        }
        $searchText = trim(strip_tags($searchText));
        return ($searchText !== '') ? $searchText : null;
    }

    protected function _getInputHTMLKey($altKey = '')
    {
        if ($this->_block->getRepeaterId() && $this->_block->getRepeaterRowId()) {
            $inputHMTLKey = 'repeater[' . $this->_block->getRepeaterId() . '][' . $this->_block->getRepeaterRowId() . ']';
        } else {
            $inputHMTLKey = 'block';
        }
        return $inputHMTLKey . '[' . $this->_block->id . ']' . ($altKey ? '[' . $altKey . ']' : '');
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

}
