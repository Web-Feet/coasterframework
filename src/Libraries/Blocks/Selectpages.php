<?php namespace CoasterCms\Libraries\Blocks;

use CoasterCms\Helpers\Cms\Page\Path;
use CoasterCms\Libraries\Builder\PageBuilder;
use CoasterCms\Libraries\Builder\ViewClasses\PageDetails;
use CoasterCms\Models\BlockSelectOption;
use CoasterCms\Models\Page;
use CoasterCms\Models\PageBlock;
use View;

class Selectpages extends Selectmultiple
{

    public function display($content, $options = [])
    {
        $pages = [];
        $page_ids = [];
        if (isset($options['reverse'])) {
            // get page_ids on which current page is selected in this block
            $currentPageId = PageBuilder::pageId(true);
            if ($currentPageId) {
                $same_blocks = PageBlock::where('block_id', '=', $this->_block->id)->get();
                foreach ($same_blocks as $same_block) {
                    $block_page_ids = @unserialize($same_block->content);
                    if (!empty($block_page_ids)) {
                        foreach ($block_page_ids as $k => $block_page_id) {
                            $block_page_id = Path::unParsePageId($block_page_id);
                            if ($currentPageId == $block_page_id) {
                                $page_ids[] = $same_block->page_id;
                                break;
                            }
                        }
                    }
                }
            }
        } elseif (!empty($content)) {
            $page_ids = unserialize($content);
        }
        if (!empty($page_ids)) {
            foreach ($page_ids as $page_id) {
                $parsedPageId = Path::unParsePageId($page_id, false);
                $pages[$page_id] = new PageDetails($parsedPageId[0], !empty($parsedPageId[1]) ? $parsedPageId[1] : 0);
            }
        }
        $template = !empty($options['view']) ? $options['view'] : $this->_block->name;
        $selectPageViews = 'themes.' . PageBuilder::getData('theme') . '.blocks.selectpages.';
        if (View::exists($selectPageViews . $template)) {
            return View::make($selectPageViews . $template, array('pages' => $pages))->render();
        } else {
            return 'Select pages template not found';
        }
    }

    public function edit($content)
    {
        $parent = BlockSelectOption::where('block_id', '=', $this->_block->id)->where('option', '=', 'parent')->first();
        $parentPageId = !empty($parent) ? $parent->value : 0;
        $this->_editViewData['selectOptions'] = Page::get_page_list(['parent' => $parentPageId]);
        return parent::edit($content);
    }

}