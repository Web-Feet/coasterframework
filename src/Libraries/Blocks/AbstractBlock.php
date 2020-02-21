<?php

namespace CoasterCms\Libraries\Blocks;

use CoasterCms\Helpers\Cms\View\CmsBlockInput;
use CoasterCms\Models\Block;
use PageBuilder;
use Request;
use View;

abstract class AbstractBlock
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
    protected $_editViewData = [];

    /**
     * @var bool
     */
    protected $_isSaved = false;

    /**
     * @var string
     */
    protected $_contentSaved = '';

    /**
     * @var array
     */
    protected $_displayViewDirs = [];

    /**
     * @var array
     */
    protected $_displayViewFiles = [];

    /**
     * @var array
     */
    protected $_displayViewCache = [];

    /**
     * @var array
     */
    protected $_displayViewLog = [];

    /**
     * @var string
     */
    protected $_renderDataName = 'data';

    /**
     * @var string
     */
    protected $_renderRepeatedItemName = 'data';

    /**
     * String_ constructor.
     * @param Block $block
     */
    public function __construct(Block $block)
    {
        $this->_block = clone $block;
        $this->_displayViewDirs = [strtolower($this->_block->type)];
        $this->_displayViewFiles = array_filter([$this->_block->name, 'default']);
    }

    /**
     * Frontend display for the block
     * @param string $content
     * @param array $options
     * @return string
     */
    public function display($content, $options = [])
    {
        return $this->_renderDisplayView($options, $content, true);
    }

    /**
     * Frontend return string or unserialized data
     * @param string $content
     * @param array $options
     * @return mixed
     */
    public function data($content, $options = [])
    {
        return $this->_defaultData($content);
    }

    /**
     * Used in theme builder to render views
     * @param array $options
     * @return string
     */
    public function displayDummy($options)
    {
        return $this->display('', $options);
    }

    /**
     * Used in theme builder to render blocks as json
     * @param string $content
     * @param array $options
     * @return string json
     */
    public function toJson($content, $options = [])
    {
        return collect([$this->_block->name => ['block' => $this->_block->toArray(), 'data' => $this->data($content)]])->toJson();
    }

    /**
     * Return display block view path
     * @param string $view
     * @param string $suffix
     * @return string
     */
    public function displayView($view = '', $suffix = '')
    {
        $viewTag = $view . ($suffix ?  '#' . $suffix : '');
        if (!array_key_exists($viewTag, $this->_displayViewCache)) {
            $this->_displayViewCache[$viewTag] = '';
            $viewRootPath = 'themes.' . PageBuilder::getData('theme') . '.blocks.';
            $viewFiles = $view ? array_merge([$view], $this->_displayViewFiles) : $this->_displayViewFiles;
            foreach ($this->_displayViewDirs as $viewDir) {
                foreach ($viewFiles as $viewFile) {
                    $this->_displayViewLog[] = $viewRootPath . $viewDir . '.' . $viewFile . $suffix;
                    if (View::exists(end($this->_displayViewLog))) {
                        $this->_displayViewCache[$viewTag] = end($this->_displayViewLog);
                        break 2;
                    }
                }
            }
        }
        return $this->_displayViewCache[$viewTag];
    }

    /**
     * @param array $options
     * @return string
     */
    public function displayViewOptions($options)
    {
        $view = array_key_exists('view', $options) ? $options['view'] : '';
        $suffix = array_key_exists('view_suffix', $options) ? $options['view_suffix'] : '';
        return $this->displayView($view, $suffix);
    }

    /**
     * Return rendered display block view
     * @param array $options
     * @param array $data
     * @param bool $returnRaw
     * @return string
     */
    protected function _renderDisplayView($options, $data = [], $returnRaw = false)
    {
        if (array_key_exists('repeated_view', $options) && $options['repeated_view']) {
            return $this->_renderRepeatedDisplayView($options, $data);
        } elseif ($displayView = $this->displayViewOptions($options)) {
            $viewData = [$this->_renderDataName => $data, 'data' => $data];
            $viewData = is_array($data) ? $data + $viewData : $viewData;
            return View::make($displayView, $viewData)->render();
        } elseif ($returnRaw) {
            return (string) $data;
        } else {
            return $this->_renderDisplayViewNotFoundError();
        }
    }

    /**
     * @param array $options
     * @param array $data
     * @return string
     */
    protected function _renderRepeatedDisplayView($options, $data = [])
    {
        if ($displayView = $this->displayViewOptions($options)) {
            $renderedContent = '';
            if (!empty($data) && is_array($data)) {
                $i = 1;
                $j = 1;
                $isFirst = true;
                $total = count($data);
                $columns = !empty($options['columns']) ? $options['columns'] : 1;
                $showColumns = !empty($options['show_columns']) ? $options['show_columns'] : [1];
                $showRows = !empty($options['selected_items']) ? $options['selected_items'] : range(1, $total); // can use to display certain rows
                $maxIndex = $total % $columns;
                $lastElement = $total - $maxIndex + max(array_filter($showColumns, function ($var) use ($maxIndex) {
                    return $var <= $maxIndex;
                }) ?: [0]);
                foreach ($data as $dataItemId => $dataItem) {
                    if (in_array((($i - 1) % $columns) + 1, $showColumns) && in_array($i, $showRows)) {
                        $isLast = ($i == $lastElement || $i == max($showRows));
                        $itemData = [
                            'data' => $dataItem,
                            'data_id' => $dataItemId,
                            $this->_renderRepeatedItemName => $dataItem,
                            $this->_renderRepeatedItemName . '_id' => $dataItemId,
                            'is_first' => $isFirst,
                            'is_last' => $isLast,
                            'count' => $j,
                            'count_all' => $i,
                            'total' => $total
                        ] + $options;

                        $renderedContent .= $this->_renderRepeatedDisplayViewItem($displayView, $itemData);
                        $isFirst = false;
                        $j++;
                    }
                    $i++;
                }
            }

            return $renderedContent;
        } else {
            return $this->_renderDisplayViewNotFoundError();
        }
    }

    /**
     * @param string $displayView
     * @param array $data
     * @return string
     */
    protected function _renderRepeatedDisplayViewItem($displayView, $data = [])
    {
        return View::make($displayView, $data)->render();
    }

    /**
     * @return string
     */
    protected function _renderDisplayViewNotFoundError()
    {
        $error = 'Template not found for ' . $this->_block->type . ' block: ' . $this->_block->name;
        foreach ($this->_displayViewLog as $k => $viewNotFound) {
            $error .= '<br />Tried #' . ($k + 1) . ' ' . $viewNotFound;
        }
        return $error;
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
        $dotName = str_replace(['[', ']'], ['.', ''], $this->_getInputHTMLKey());
        $submittedData = Request::input($dotName);
        return CmsBlockInput::make($this->_block->type, $this->_editViewData + [
            'label' => $this->_block->label,
            'name' => $this->_getInputHTMLKey(),
            'content' => $content,
            'submitted_data' => $submittedData ? $this->_defaultData($submittedData) : null,
            'note' => $this->_block->note,
            '_block' => $this->_block,
            '_blockType' => $this
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
            $inputHTMLKey = 'repeater[' . $this->_block->getRepeaterId() . '][' . $this->_block->getRepeaterRowId() . ']';
        } else {
            $inputHTMLKey = 'block';
        }
        return $inputHTMLKey . '[' . $this->_block->id . ']' . ($altKey ? '[' . $altKey . ']' : '');
    }

    /**
     * Admin update using post data from the block view
     * @param mixed $postContent
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
     * Return string or if content is serialized return unserialized data
     * @param string $content
     * @return mixed
     */
    protected function _defaultData($content)
    {
        return $content;
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
