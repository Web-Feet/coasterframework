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
use CoasterCms\Models\PageBlockRepeaterRows;
use Illuminate\Pagination\LengthAwarePaginator;
use Request;
use Validator;
use View;

class Repeater extends String_
{
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
            if ($repeaterBlocks = BlockRepeater::getRepeaterBlocks($this->_block->id)) {

                $random = !empty($options['random']) ? $options['random'] : false;
                $repeaterRows = PageBlockRepeaterData::loadRepeaterData($repeaterId, $options['version'], $random);

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
                            foreach ($repeaterBlocks as $repeaterBlock) {
                                if ($repeaterBlock->exists) {
                                    PageBuilder::setCustomBlockData($repeaterBlock->name, !empty($row[$repeaterBlock->id]) ? $row[$repeaterBlock->id] : '', null, false);
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
        $repeaterId = $content ?: PageBlockRepeaterRows::nextFreeRepeaterId();
        $this->_editViewData['renderedRows'] = '';

        if ($repeaterBlocks = BlockRepeater::getRepeaterBlocks($this->_block->id)) {
            // check if new or existing row needs displaying
            if ($newRow) {
                $renderedRow = '';
                $repeaterRowId = PageBlockRepeaterRows::nextFreeRepeaterRowId($repeaterId);
                foreach ($repeaterBlocks as $repeaterBlock) {
                    $renderedRow .= $repeaterBlock->setPageId($this->_block->getPageId())->setRepeaterData($repeaterId, $repeaterRowId)->getTypeObject()->edit('');
                }
                return (string) CmsBlockInput::make('repeater.row', ['repeater_id' => $repeaterId, 'row_id' => $repeaterRowId, 'blocks' => $renderedRow]);
            } else {
                $repeaterRowsData = PageBlockRepeaterData::loadRepeaterData($repeaterId, $this->_block->getVersionId());
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
                    foreach ($existingRepeaterRow as $blockId => $existingRepeaterBlockContent) {
                        $block = Block::preloadClone($blockId);
                        $block->id = $blockId;
                        if ($block->exists || $blockId === 0) {
                            $block->setVersionId($this->_block->getVersionId())->setRepeaterData($postContent['repeater_id'], $rowId)->setPageId($this->_block->getPageId())->getTypeObject()->save('');
                        }
                    }
                }
            }
        }

        // save new data
        $rowOrderNumber = 0;
        foreach ($submittedRepeaterRows as $rowId => $submittedRepeaterRow) {
            $rowOrderNumber++;
            foreach ($submittedRepeaterRow as $submittedBlockId => $submittedBlockData) {
                $block = Block::preloadClone($submittedBlockId)->setVersionId($this->_block->getVersionId())->setRepeaterData($postContent['repeater_id'], $rowId)->setPageId($this->_block->getPageId());
                if ($block->exists || $submittedBlockId == 0) {
                    $block->id = $submittedBlockId;
                    $block->getTypeObject()->submit($submittedBlockData);
                }
            }
        }

        return $return;
    }

    /**
     * Get search text from repeater blocks
     * @param null|string $content
     * @return null|string
     */
    public function generateSearchText($content)
    {
        $searchText = '';
        $repeaterRows = PageBlockRepeaterData::loadRepeaterData($content, $this->_block->getVersionId());
        $repeaterBlocks = BlockRepeater::getRepeaterBlocks($this->_block->id);

        foreach ($repeaterRows as $rowId => $repeaterRow) {
            foreach ($repeaterBlocks as $blockId => $repeaterBlock) {
                if(($blockContent = array_key_exists($blockId, $repeaterRow) ? $repeaterRow[$blockId] : null) !== null) {
                    $block = Block::preloadClone($blockId)->setRepeaterData($this->_block->getRepeaterId(), $this->_block->getRepeaterRowId())->setPageId($this->_block->getPageId());
                    if ($block->exists && $block->search_weight > 0) {
                        $blockSearchText = $block->getTypeObject()->generateSearchText($blockContent);
                        $searchText .= ($blockSearchText !== null) ? $blockSearchText . "\n" : '';
                    }
                }
            }
        }

        return parent::generateSearchText($searchText);
    }

    /**
     * Add new repeater row with passed block contents onto end of repeater rows (or create repeater and set as first row)
     * @param array $repeaterBlockContents
     */
    public function insertRow($repeaterBlockContents)
    {
        if (!($repeaterId = $this->_block->getRepeaterId())) {
            $repeaterId = PageBlockRepeaterRows::nextFreeRepeaterId();
            $this->save($repeaterId);
            $currentRepeaterRows = [];
        } else {
            $currentRepeaterRows = PageBlockRepeaterData::loadRepeaterData($repeaterId);
        }
        $repeaterRowId = PageBlockRepeaterRows::nextFreeRepeaterRowId($repeaterId);

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
