<?php namespace CoasterCms\Libraries\Blocks;

use CoasterCms\Helpers\Cms\StringHelper;
use CoasterCms\Helpers\Cms\View\CmsBlockInput;
use CoasterCms\Libraries\Builder\PageBuilder;
use CoasterCms\Models\Block;
use View;

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
     * @var array
     */
    protected $_displayViewPriorities;

    /**
     * @var array
     */
    protected $_processedDisplayView;

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
        $this->_processedDisplayView = [];
        $this->_displayViewPriorities = [$this->_block->name, 'default'];
    }

    /**
     * Frontend display for the block
     * @param string $content
     * @param array $options
     * @return string
     */
    public function display($content, $options = [])
    {
        if (!empty($options['meta']) || !empty($options['pageBuilder'])) {
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
     * Return display block view path
     * @param string|array view
     * @return string
     */
    protected function _displayView($view = '')
    {
        if (is_array($view)) {
            $viewSuffix = !empty($view['view_suffix']) ? $view['view_suffix'] : '';
            $view = !empty($view['view']) ? $view['view'] : '';
        } else {
            $viewSuffix = '';
        }
        $pView = $view . '#' . $viewSuffix;
        if (!array_key_exists($pView, $this->_processedDisplayView)) {
            $this->_processedDisplayView[$pView] = '';
            $viewRootPath = 'themes.' . PageBuilder::getData('theme') . '.blocks.' . strtolower($this->_block->type) . 's.';
            if ($view) {
                array_unshift($this->_displayViewPriorities, $view);
            }
            foreach ($this->_displayViewPriorities as $viewPriority) {
                if (View::exists($viewRootPath . $viewPriority . $viewSuffix)) {
                    $this->_processedDisplayView[$pView] = $viewRootPath . $viewPriority . $viewSuffix;
                    break;
                }
            }
        }
        return $this->_processedDisplayView[$pView];
    }

    /**
     * Return rendered display block view
     * @param string|array view
     * @param array $data
     * @return string
     */
    protected function _renderDisplayView($view, $data = [])
    {
        if ($displayView = $this->_displayView($view)) {
            return View::make($displayView, $data)->render();
        } else {
            return ucwords($this->_block->type) . ' template not found for block: ' . $this->_block->name;
        }
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
     * Should only be called after save
     * By default, updates search text and publishes a new page version
     * @return static
     */
    public function publish()
    {
        if ($this->_block->getPageId() && $this->_isSaved) {
            $searchText = $this->_block->search_weight > 0 ? $this->generateSearchText($this->_contentSaved) : '';
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
        return $content;
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
