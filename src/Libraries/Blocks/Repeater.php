<?php namespace CoasterCms\Libraries\Blocks;

use Carbon\Carbon;
use CoasterCms\Helpers\Cms\Email;
use CoasterCms\Helpers\Cms\View\CmsBlockInput;
use CoasterCms\Helpers\Cms\View\FormWrap;
use CoasterCms\Helpers\Cms\View\PaginatorRender;
use CoasterCms\Libraries\Builder\FormMessage;
use CoasterCms\Libraries\Builder\PageBuilder;
use CoasterCms\Models\Block;
use CoasterCms\Models\BlockFormRule;
use CoasterCms\Models\BlockRepeater;
use CoasterCms\Models\PageBlockRepeaterData;
use Illuminate\Pagination\LengthAwarePaginator;
use Request;
use Validator;
use View;

class Repeater extends String_
{
    /**
     * @var string
     */
    protected $_repeaterContentSaved;

    /**
     * Display repeater view
     * @param string $content
     * @param array $options
     * @return string
     */
    public function display($content, $options = [])
    {
        $repeaterId = $content;
        $template = !empty($options['view']) ? $options['view'] : $this->_block->name;
        $repeatersViews = 'themes.' . PageBuilder::getData('theme') . '.blocks.repeaters.';

        if (!empty($options['form'])) {
            return FormWrap::view($this->_block, $options, $repeatersViews . $template . '-form');
        }

        if (View::exists($repeatersViews . $template)) {
            $renderedContent = '';
            if ($repeaterBlockNames = BlockRepeater::preload($this->_block->id)) {
                $repeaterBlockNameArray = explode(',', $repeaterBlockNames->blocks);

                $random = !empty($options['random']) ? $options['random'] : false;
                $repeaterRows = PageBlockRepeaterData::load_by_repeater_id($repeaterId, $options['version'], $random);

                // pagination
                if (!empty($options['per_page']) && !empty($repeaterRows)) {
                    $pagination = new LengthAwarePaginator($repeaterRows, count($repeaterRows), $options['per_page'], Request::input('page', 1));
                    $pagination->setPath(Request::getPathInfo());
                    $paginationLinks = PaginatorRender::run($pagination);
                    $repeaterRows = array_slice($repeaterRows, (($pagination->currentPage() - 1) * $options['per_page']), $options['per_page']);
                } else {
                    $paginationLinks = '';
                }

                if (!empty($repeaterRows)) {
                    $i = 1;
                    $isFirst = true;
                    $isLast = false;
                    $rows = count($repeaterRows);
                    $cols = !empty($options['cols']) ? (int)$options['cols'] : 1;
                    $column = !empty($options['column']) ? (int)$options['column'] : 1;
                    foreach ($repeaterRows as $row) {
                        if ($i % $cols == $column % $cols) {
                            $previousKey = PageBuilder::getCustomBlockDataKey();
                            PageBuilder::setCustomBlockDataKey('repeater' . $repeaterId . '.' . $i);
                            foreach ($repeaterBlockNameArray as $repeaterBlockName) {
                                $block = Block::preload($repeaterBlockName);
                                if ($block->exists) {
                                    PageBuilder::setCustomBlockData($block->name, !empty($row[$repeaterBlockName]) ? $row[$repeaterBlockName] : '', null, false);
                                }
                            }
                            if ($i + $cols - 1 >= $rows) {
                                $isLast = true;
                            }
                            $renderedContent .= View::make($repeatersViews . $template, array('is_first' => $isFirst, 'is_last' => $isLast, 'count' => $i, 'total' => $rows, 'id' => $repeaterId, 'pagination' => $paginationLinks, 'links' => $paginationLinks))->render();
                            $isFirst = false;
                            PageBuilder::setCustomBlockDataKey($previousKey);
                        }
                        $i++;
                    }
                }
            }
            return $renderedContent;
        } else {
            return "Repeater view does not exist in theme";
        }
    }

    /**
     * Repeater form submission
     * @param array $formData
     * @return null|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function submission($formData)
    {
        $formRules = BlockFormRule::get_rules($this->_block->name.'-form');
        $v = Validator::make($formData, $formRules);
        if ($v->passes()) {

            foreach ($formData as $blockName => $content) {
                $fieldBlock = Block::preload($blockName);
                if ($fieldBlock->exists) {
                    if ($fieldBlock->type == 'datetime' && empty($content)) {
                        $content = new Carbon();
                    }
                    $formData[$blockName] = $content;
                }
            }

            $this->insertRow($formData);

            Email::sendFromFormData([$this->_block->name.'-form'], $formData, config('coaster::site.name') . ': New Form Submission - ' . $this->_block->label);

            return \redirect(Request::url());

        } else {
            FormMessage::set($v->messages());
        }

        return null;
    }

    /**
     * Admin view for editing repeater data
     * Can return an individual repeater row with default block data)
     * @param string $content
     * @param bool $newRow
     * @return string
     */
    public function edit($content, $newRow = false)
    {
        // if no current repeater id, reserve next new repeater id for use on save
        $repeaterId = $content ?: PageBlockRepeaterData::next_free_repeater_id();
        $this->_editViewData['renderedRows'] = '';

        if ($repeaterBlock = BlockRepeater::where('block_id', '=', $this->_block->id)->first()) {
            // load repeater blocks
            $repeaterBlocks = [];
            foreach (Block::whereIn('id', explode(",", $repeaterBlock->blocks))->orderBy('order', 'asc')->get() as $repeaterBlock) {
                $repeaterBlocks[$repeaterBlock->id] = $repeaterBlock;
            }

            // check if new or existing row needs displaying
            if ($newRow) {
                $renderedRow = '';
                $repeaterRowId = PageBlockRepeaterData::next_free_row_id($repeaterId);
                foreach ($repeaterBlocks as $repeaterBlock) {
                    $renderedRow .= $repeaterBlock->setPageId($this->_block->getPageId())->setRepeaterData($repeaterId, $repeaterRowId)->getTypeObject()->edit('');
                }
                return (string) CmsBlockInput::make('repeater.row', array('repeater_id' => $repeaterId, 'row_id' => $repeaterRowId, 'blocks' => $renderedRow));
            } else {
                $repeaterRowsData = PageBlockRepeaterData::load_by_repeater_id($repeaterId, $this->_block->getVersionId());
                foreach ($repeaterRowsData as $repeaterRowId => $repeaterRowData) {
                    $renderedRow = '';
                    foreach ($repeaterBlocks as $repeaterBlockId => $repeaterBlock) {
                        $fieldContent = isset($repeaterRowData[$repeaterBlockId]) ? $repeaterRowData[$repeaterBlockId] : '';
                        $renderedRow .= $repeaterBlock->setPageId($this->_block->getPageId())->setRepeaterData($repeaterId, $repeaterRowId)->getTypeObject()->edit($fieldContent);
                    }
                    $this->_editViewData['renderedRows'] .= CmsBlockInput::make('repeater.row', ['repeater_id' => $repeaterId, 'row_id' => $repeaterRowId, 'blocks' => $renderedRow]);
                }
            }
        }

        $this->_editViewData['_repeaterId'] = $this->_block->getRepeaterId();
        $this->_editViewData['_repeaterRowId'] = $this->_block->getRepeaterRowId();

        return parent::edit($repeaterId);
    }

    /**
     * Save submitted repeater data
     * @param array $postContent
     * @return static
     */
    public function submit($postContent)
    {
        $return = $this->save($postContent['repeater_id']);

        // load current and submitted data
        $existingRepeaterRows = PageBlockRepeaterData::loadRepeaterData($postContent['repeater_id'], $this->_block->getVersionId());
        $submittedRepeaterRows = Request::input('repeater.' . $postContent['repeater_id']) ?: [];

        // if row missing, overwrite all data with blanks in new version
        if ($existingRepeaterRows) {
            foreach ($existingRepeaterRows as $rowId => $existingRepeaterRow) {
                if (empty($submittedRepeaterRows[$rowId])) {
                    foreach ($existingRepeaterRow as $existingRepeaterBlock) {
                        $block = Block::preloadClone($existingRepeaterBlock->block_id);
                        if ($block->exists) {
                            $block->setVersionId($this->_block->getVersionId())->setRepeaterData($postContent['repeater_id'], $rowId)->setPageId($this->_block->getPageId())->getTypeObject()->save('');
                        }
                    }
                }
            }
        }

        // save new data
        $this->_repeaterContentSaved = '';
        $rowOrderNumber = 0;
        foreach ($submittedRepeaterRows as $rowId => $submittedRepeaterRow) {
            $rowOrderNumber++;
            foreach ($submittedRepeaterRow as $submittedBlockId => $submittedBlockData) {
                $block = Block::preloadClone($submittedBlockId)->setVersionId($this->_block->getVersionId())->setRepeaterData($postContent['repeater_id'], $rowId)->setPageId($this->_block->getPageId());
                if ($block->exists || $submittedBlockId == 0) {
                    if ($block->exists) {
                        $blockTypeObject = $block->getTypeObject()->submit($submittedBlockData);
                        $savedContent = $blockTypeObject->generateSearchText($blockTypeObject->getSavedContent());
                        $this->_repeaterContentSaved .= ($savedContent !== null) ? $savedContent . "\n" : '';
                    } else {
                        $block->id = $submittedBlockId;
                        $block->getTypeObject()->save($rowOrderNumber);
                    }
                }
            }
        }

        return $return;
    }

    /**
     * Change saved content function to return search text from blocks inside the repeater
     * @return string
     */
    public function getSavedContent()
    {
        return $this->_repeaterContentSaved;
    }

    /**
     * Add new repeater row with passed block contents onto end of repeater rows (or create repeater and set as first row)
     * @param array $repeaterBlockContents
     */
    public function insertRow($repeaterBlockContents)
    {
        if (!($repeaterId = $this->_block->getRepeaterId())) {
            $repeaterId = PageBlockRepeaterData::next_free_repeater_id();
            $this->save($repeaterId);
            $currentRepeaterRows = [];
        } else {
            $currentRepeaterRows = PageBlockRepeaterData::load_by_repeater_id($repeaterId);
        }
        $repeaterRowId = PageBlockRepeaterData::next_free_row_id($repeaterId);

        if (!array_key_exists(0, $repeaterBlockContents)) {
            if (!empty($currentRepeaterRows)) {
                $rowOrders = array_map(function ($row) {return !empty($row[0]) ? $row[0] : 0;}, $currentRepeaterRows);
                $repeaterBlockContents[0] = max($rowOrders) + 1;
            } else {
                $repeaterBlockContents[0] = 1;
            }
        }

        foreach ($repeaterBlockContents as $blockName => $content) {
            $block = Block::preloadClone($blockName);
            if ($block->exists || $blockName == 0) {
                $block->setVersionId($this->_block->getVersionId())->setRepeaterData($repeaterId, $repeaterRowId)->setPageId($this->_block->getPageId())->getTypeObject()->save($content);
            }
        }
    }

}
