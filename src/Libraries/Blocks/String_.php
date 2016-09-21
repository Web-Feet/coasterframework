<?php namespace CoasterCms\Libraries\Blocks;

use CoasterCms\Helpers\Cms\StringHelper;
use CoasterCms\Helpers\Cms\View\CmsBlockInput;
use CoasterCms\Libraries\Builder\PageBuilder;
use CoasterCms\Models\Block;

class String_
{
    /**
     * @var array
     */
    public static $blockSettings = [];

    /**
     * @var Block
     */
    protected $_block;

    /**
     * @var array
     */
    protected $_editViewData;

    /**
     * @var bool
     */
    protected $_isSaved;

    /**
     * @var string
     */
    protected $_contentSaved;

    /**
     * String_ constructor.
     * @param Block $block
     */
    public function __construct(Block $block)
    {
        $this->_block = clone $block;
        $this->_editViewData = [];
        $this->_isSaved = false;
        $this->_contentSaved = '';
    }

    /**
     * Frontend display for the block
     * @param string $content
     * @param array $options
     * @return string
     */
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

    /**
     * Frontend form submission
     * @param array $formData
     * @return null|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function submission($formData)
    {
        return null;
    }

    /**
     * Admin display for the block
     * @param string $content
     * @return string
     */
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

    /**
     * Create the html key to be used in the block view
     * @param string $altKey
     * @return string
     */
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
     * Admin update using post data from the block view
     * @param array $postContent
     * @return static
     */
    public function submit($postContent)
    {
        return $this->save($postContent);
    }

    /**
     * Update block, raw data should be string
     * @param string $content
     * @return static
     */
    public function save($content)
    {
        $this->_isSaved = true;
        $this->_contentSaved = (string) $content;
        $this->_block->updateContent($this->_contentSaved);
        return $this;
    }

    /**
     * Gets saved content, used in publish function to generate search text
     * @return string
     */
    public function getSavedContent()
    {
        return $this->_contentSaved;
    }

    /**
     * Should only be called after save
     * By default, updates search text and publishes a new page version
     * @return static
     */
    public function publish()
    {
        if ($this->_block->getPageId() && $this->_isSaved) {
            $searchText = $this->generateSearchText($this->getSavedContent());
            $this->_block->publishContent($searchText);
        }
        return $this;
    }

    /**
     * Generate search text from saved content
     * @param null|string $content
     * @return null|string
     */
    public function generateSearchText($content)
    {
        return $this->_generateSearchText($content);
    }

    /**
     * Joins all non whitespace parameters passed through as and returns string or null if only whitespace
     * Also removes HTML tags
     * @param array ...$contentParts
     * @return null|string
     */
    protected function _generateSearchText(...$contentParts)
    {
        $searchText = '';
        foreach ($contentParts as $contentPart) {
            $contentPart = trim((string) $contentPart);
            if ($contentPart !== '') {
                $searchText .= $contentPart . ' ';
            }
        }
        $searchText = trim(strip_tags($searchText));
        return ($searchText !== '') ? $searchText : null;
    }

    /**
     * Used by the PageBuilder filter functions to filter block data
     * @param string $content
     * @param string $search
     * @param string $type
     * @return bool
     */
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

    /**
     * Theme export function, returns array of file paths used by this block
     * @param string $content
     * @return array
     */
    public function exportFiles($content)
    {
        return [];
    }

}
