<?php namespace CoasterCms\Http\Controllers\AdminControllers;

use CoasterCms\Http\Controllers\AdminController as Controller;
use CoasterCms\Models\Block;
use Request;

class RepeatersController extends Controller
{

    public function postIndex()
    {
        $block = Block::find(Request::input('block_id'));
        if  (($repeaterId = Request::input('repeater_id')) && $block && $block->type == 'repeater') {
            return $block->setPageId(Request::input('page_id'))->getTypeObject()->edit($repeaterId, true);
        }
        return 0;
    }

}