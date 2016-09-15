<?php namespace CoasterCms\Libraries\Blocks;

use Carbon\Carbon;
use CoasterCms\Helpers\Cms\Email;
use CoasterCms\Helpers\Cms\Theme\BlockManager;
use CoasterCms\Helpers\Cms\View\CmsBlockInput;
use CoasterCms\Helpers\Cms\View\FormWrap;
use CoasterCms\Helpers\Cms\View\PaginatorRender;
use CoasterCms\Libraries\Builder\FormMessage;
use CoasterCms\Libraries\Builder\PageBuilder;
use CoasterCms\Models\Block;
use CoasterCms\Models\BlockFormRule;
use CoasterCms\Models\BlockRepeater;
use CoasterCms\Models\Language;
use CoasterCms\Models\PageBlock;
use CoasterCms\Models\PageBlockRepeaterData;
use CoasterCms\Models\PageSearchData;
use CoasterCms\Models\PageVersion;
use Illuminate\Pagination\LengthAwarePaginator;
use Request;
use Validator;
use View;

class Repeater extends String_
{
    public static $blocks_key = 'repeater';

    private static $_preloaded_repeater_data;
    private static $_current_repeater = null;

    protected $_newRow;

    public function __construct(Block $block)
    {
        parent::__construct($block);
        $this->_newRow = false;
    }

    public function display($repeaterId, $options = [])
    {
        $template = !empty($options['view']) ? $options['view'] : $this->_block->name;
        $repeatersViews = 'themes.' . PageBuilder::getData('theme') . '.blocks.repeaters.';

        if (!empty($options['form'])) {
            return FormWrap::view($this->_block, $options, $repeatersViews . $template . '-form');
        }

        if (View::exists($repeatersViews . $template)) {
            $renderedContent = '';
            if ($rep_blocks = BlockRepeater::preload($this->_block->id)) {
                $rep_blocks = explode(',', $rep_blocks->blocks);

                $random = !empty($options['random']) ? $options['random'] : false;
                $repeaterRows = PageBlockRepeaterData::load_by_repeater_id($repeaterId, $options['version'], $random);

                // pagination
                if (!empty($options['per_page']) && !empty($repeaterRows)) {
                    $block_rows_paginator = new LengthAwarePaginator($repeaterRows, count($repeaterRows), $options['per_page'], Request::input('page', 1));
                    $block_rows_paginator->setPath(Request::getPathInfo());
                    $links = PaginatorRender::run($block_rows_paginator);
                    $repeaterRows = array_slice($repeaterRows, (($block_rows_paginator->currentPage() - 1) * $options['per_page']), $options['per_page']);
                } else {
                    $links = '';
                }

                if (!empty($repeaterRows)) {
                    $i = 1;
                    $is_first = true;
                    $is_last = false;
                    $rows = count($repeaterRows);
                    $cols = !empty($options['cols']) ? (int)$options['cols'] : 1;
                    $column = !empty($options['column']) ? (int)$options['column'] : 1;
                    $previous = self::$_current_repeater;
                    self::$_current_repeater = $repeaterId;
                    self::$_preloaded_repeater_data[$repeaterId] = array();
                    foreach ($repeaterRows as $row) {
                        if ($i % $cols == $column % $cols) {
                            foreach ($rep_blocks as $rep_block) {
                                // save block data for when view is being processed
                                $block_info = Block::preload($rep_block);
                                if ($block_info->exists) {
                                    if (!empty($row[$rep_block])) {
                                        self::$_preloaded_repeater_data[$repeaterId][$block_info->name] = $row[$rep_block];
                                    } else {
                                        self::$_preloaded_repeater_data[$repeaterId][$block_info->name] = '';
                                    }
                                }
                            }
                            if ($i + $cols - 1 >= $rows)
                                $is_last = true;
                            $renderedContent .= View::make($repeatersViews . $template, array('is_first' => $is_first, 'is_last' => $is_last, 'count' => $i, 'total' => $rows, 'id' => $block_data, 'pagination' => $links, 'links' => $links))->render();
                            $is_first = false;
                        }
                        $i++;
                    }
                    self::$_current_repeater = $previous;
                }
            }
            return $renderedContent;
        } else {
            return "Repeater view does not exist in theme";
        }
    }

    public function submission($formData)
    {
        $formRules = BlockFormRule::get_rules($this->_block->name.'-form');
        $v = Validator::make($formData, $formRules);
        if ($v->passes()) {

            $pageId = !empty($formData['page_id']) ? $formData['page_id'] : 0;
            unset($formData['page_id']);

            foreach ($formData as $blockName => $content) {
                $fieldBlock = Block::preload($blockName);
                if ($fieldBlock->exists) {
                    if ($fieldBlock->type == 'datetime' && empty($content)) {
                        $content = new Carbon();
                    }
                    $formData[$blockName] = $content;
                }
            }
            self::insertRow($this->_block->id, $pageId, $formData);

            Email::sendFromFormData([$this->_block->name.'-form'], $formData, config('coaster::site.name') . ': New Form Submission - ' . $this->_block->label);

            return \redirect(Request::url());

        } else {
            FormMessage::set($v->messages());
        }

        return false;
    }

    public function edit($repeaterId)
    {
        // if no current repeater id, reserve next new repeater id for use on save
        $repeaterId = $repeaterId ?: PageBlockRepeaterData::next_free_repeater_id();
        $content = '';

        if ($repeaterBlock = BlockRepeater::where('block_id', '=', $this->_block->id)->first()) {
            // load repeater blocks
            $repeaterBlocks = [];
            foreach (Block::whereIn('id', explode(",", $repeaterBlock->blocks))->orderBy('order', 'asc')->get() as $repeaterBlock) {
                $repeaterBlocks[$repeaterBlock->id] = $repeaterBlock;
            }

            // check if new or existing row needs displaying
            if ($this->_newRow) {
                $renderedRow = '';
                $repeaterRowId = PageBlockRepeaterData::next_free_row_id($repeaterId);
                foreach ($repeaterBlocks as $repeaterBlock) {
                    $renderedRow .= $repeaterBlock->getTypeObject()->setPageId($this->_pageId)->setRepeaterData($repeaterId, $repeaterRowId)->edit('');
                }
                return CmsBlockInput::make('repeater.row', array('repeater_id' => $repeaterId, 'row_id' => $repeaterRowId, 'blocks' => $renderedRow));
            } else {
                $repeaterRowsData = PageBlockRepeaterData::load_by_repeater_id($repeaterId, BlockManager::$current_version);
                foreach ($repeaterRowsData as $repeaterRowId => $repeaterRowData) {
                    $renderedRow = '';
                    foreach ($repeaterBlocks as $repeaterBlockId => $repeaterBlock) {
                        $fieldContent = isset($repeaterRowData[$repeaterBlockId]) ? $repeaterRowData[$repeaterBlockId] : '';
                        $renderedRow .= $repeaterBlock->getTypeObject()->setPageId($this->_pageId)->setRepeaterData($repeaterId, $repeaterRowId)->edit($fieldContent);
                    }
                    $content .= CmsBlockInput::make('repeater.row', array('repeater_id' => $repeaterId, 'row_id' => $repeaterRowId, 'blocks' => $renderedRow));
                }
            }
        }

        return $content;
    }

    public static function submit($postDataKey = '')
    {
        if (empty(BlockManager::$to_version)) {
            $new_version = PageVersion::add_new($page_id);
            BlockManager::$to_version = $new_version->version_id;
        }

        // load current and submitted data
        $existing_repeaters = PageBlockRepeaterData::get_page_repeater_data($page_id, BlockManager::$to_version);
        $submitted_repeaters = Request::input('repeater_id');
        $repeaters = Request::input($blocks_key);

        // check changes in new / deleted - repeaters / rows
        if (!empty($submitted_repeaters)) {
            foreach ($submitted_repeaters as $submitted_repeater_id => $submitted_repeater) {
                if (isset($existing_repeaters[$submitted_repeater_id])) {
                    foreach ($existing_repeaters[$submitted_repeater_id] as $row_id => $row) {
                        if (empty($repeaters[$submitted_repeater_id][$row_id])) {
                            foreach ($row as $row_block) {
                                // if row missing, overwrite all data with blanks in new version
                                $repeater_info = new \stdClass;
                                $repeater_info->repeater_id = $row_block->repeater_id;
                                $repeater_info->row_id = $row_block->row_id;
                                BlockManager::update_block($row_block->block_id, '', 0, $repeater_info);
                            }
                        }
                    }
                } else {
                    // if new repeater submitted, save repeater data
                    $repeater_info = !empty($submitted_repeater['parent_repeater']) ? unserialize($submitted_repeater['parent_repeater']) : null;
                    BlockManager::update_block($submitted_repeater['block_id'], $submitted_repeater_id, $submitted_repeater['page_id'], $repeater_info);
                }
            }
        }

        foreach ($existing_repeaters as $repeater_id => $existing_repeater) {
            if (empty($existing_repeater) && empty($repeaters[$repeater_id]) && !empty($submitted_repeaters[$repeater_id])) {
                BlockManager::update_block($submitted_repeaters[$repeater_id]['block_id'], '', $page_id);
            }
        }

        if (!empty($repeaters)) {
            foreach ($repeaters as $repeater_id => $repeater) {
                $repeater_info = new \stdClass;
                $repeater_info->repeater_id = $repeater_id;
                $i = 1;
                foreach ($repeater as $row_id => $row) {
                    $repeater_info->row_id = $row_id;
                    // update order value (in block_id 0)
                    if (empty($existing_repeaters[$repeater_id][$row_id][0]) || $i != $existing_repeaters[$repeater_id][$row_id][0]->content) {
                        BlockManager::update_block(0, $i, 0, $repeater_info);
                    }
                    $i++;
                    // submit text inputs
                    BlockManager::submit_text($page_id, $blocks_key . '.' . $repeater_id . '.' . $row_id . '.', $repeater_info);
                    // submit custom data
                    BlockManager::submit_custom_block_data($page_id, $blocks_key . '.' . $repeater_id . '.' . $row_id . '.', $repeater_info);
                }
            }

            // search text update
            if (!empty(BlockManager::$publish) && !empty($page_id)) {
                $repeater_block_ids = Block::getBlockIdsOfType('repeater');
                $repeater_page_blocks = BlockManager::get_data_for_version(new PageBlock, BlockManager::$to_version, array('page_id', 'block_id', 'language_id'), array($page_id, $repeater_block_ids, Language::current()));
                foreach ($repeater_page_blocks as $repeater_page_block) {
                    $search_text = self::search_text($repeater_page_block->content, BlockManager::$to_version);
                    PageSearchData::update_processed_text($repeater_page_block->block_id, $search_text, $repeater_page_block->page_id, Language::current());
                }
            }
        }

    }

    public function setNewRow()
    {
        $this->_newRow = true;
    }

    public function search_text($repeater_id, $version = 0)
    {
        $search_text = '';
        $repeaters_data = PageBlockRepeaterData::get_sub_repeater_data($repeater_id, $version);
        if (!empty($repeaters_data)) {
            foreach ($repeaters_data as $repeater_data) {
                $block = Block::preload($repeater_data->block_id);
                if ($block->exists) {
                    $block_model = __NAMESPACE__ . '\\' . ucwords($block->type);
                    if (method_exists($block_model, 'search_text') && $block_model != 'CoasterCms\Libraries\Blocks\Repeater') {
                        $block_search_text = $block_model::search_text($repeater_data->content);
                        if (!empty($block_search_text)) {
                            $search_text .= $block_search_text . "\r\n";
                        }
                    }
                }
            }
        }
        return $search_text;
    }

    // repeater specific functions below

    /**
     * @param int|string $blockName (id/name)
     * @param int $pageId
     * @param array $contentArr (block id/name => block content)
     * @return \stdClass|false
     */
    public static function insertRow($blockName, $pageId, $contentArr)
    {
        $repeaterBlock = Block::preload($blockName);
        if ($repeaterBlock->type == 'repeater') {
            $currentVersion = $pageId ? PageVersion::latest_version($pageId) : 0;

            $repeaterInfo = new \stdClass;
            $repeaterInfo->repeater_id = BlockManager::get_block($repeaterBlock->id, $pageId, null, $currentVersion);

            if (!$repeaterInfo->repeater_id) {
                $repeaterInfo->repeater_id = PageBlockRepeaterData::next_free_repeater_id();
                BlockManager::update_block($repeaterBlock->id, $repeaterInfo->repeater_id, $pageId, null);
            }

            $repeaterInfo->row_id = PageBlockRepeaterData::next_free_row_id($repeaterInfo->repeater_id);

            $rows = PageBlockRepeaterData::load_by_repeater_id($repeaterInfo->repeater_id);
            $rowOrders = !$rows ? [0] : array_map(function ($row) {
                return !empty($row[0]) ? $row[0] : 0;
            }, $rows);

            // set a version thar all block updates are done to
            BlockManager::$to_version = BlockManager::$to_version ?: PageVersion::add_new($pageId)->version_id;

            BlockManager::update_block(0, max($rowOrders) + 1, $pageId, $repeaterInfo);
            foreach ($contentArr as $blockName => $content) {
                $block = Block::preload($blockName);
                if ($block->exists) {
                    BlockManager::update_block($block->id, $content, $pageId, $repeaterInfo);
                }
            }

            return $repeaterInfo;
        }
        return false;
    }

    public static function new_row()
    {
        $block = Block::find(Request::input('block_id'));
        if (!empty($block) && $block->type == 'repeater' && $repeaterId = Request::input('repeater_id')) {
            return $block->getTypeObject()->setPageId(Request::input('page_id'))->setNewRow()->edit($repeaterId);
        }
        return 0;
    }

    public static function load_repeater_data($block_name)
    {
        if (isset(self::$_preloaded_repeater_data[self::$_current_repeater][$block_name])) {
            return self::$_preloaded_repeater_data[self::$_current_repeater][$block_name];
        }
        return false;
    }


}
