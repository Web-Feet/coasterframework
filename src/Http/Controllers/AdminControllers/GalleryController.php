<?php namespace CoasterCms\Http\Controllers\AdminControllers;

use CoasterCms\Http\Controllers\AdminController as Controller;
use CoasterCms\Libraries\Blocks\Gallery;
use CoasterCms\Models\Page;
use CoasterCms\Models\PageLang;
use CoasterCms\Models\Template;
use Request;
use View;

class GalleryController extends Controller
{

    public function getList($pageId = 0)
    {
        $page = Page::find($pageId);
        if (!empty($page)) {
            $block_cats = Template::template_blocks(config('coaster::frontend.theme'), $page->template);
            foreach ($block_cats as $block_cat) {
                foreach ($block_cat as $block) {
                    if ($block->type == 'gallery') {
                        $gallery_blocks[] = $block;
                    }
                }
            }
        }
        if (isset($gallery_blocks)) {
            if (count($gallery_blocks) == 1) {
                return \redirect()->route('coaster.admin.gallery.edit', ['pageId' => $pageId, 'blockId' => $gallery_blocks[0]->id]);
            }
            $page_lang_data = PageLang::preload($pageId);
            if (!empty($page_lang_data)) {
                $name = $page_lang_data->name;
                if ($page->parent != 0) {
                    $parent_lang_data = PageLang::preload($page->parent);
                    $name = $parent_lang_data->name . " / " . $name;
                }
            } else {
                $name = '';
            }
            $this->layoutData['content'] = View::make('coaster::pages.gallery.list', array('page_name' => $name, 'page_id' => $pageId, 'galleries' => $gallery_blocks));
        } else {
            $this->layoutData['content'] = 'No Galleries Found';
        }
        return null;
    }

    // main actions

    public function getEdit($pageId = 0, $blockId = 0)
    {
        $this->layoutData['content'] = Gallery::page($blockId, $pageId);
    }

    public function getUpdate($pageId = 0, $blockId = 0)
    {
        return Gallery::run_handler($blockId, $pageId);
    }

    // ajax updates

    public function postCaption($pageId = 0, $blockId = 0)
    {
        return Gallery::caption($blockId, $pageId);
    }

    public function postSort($pageId = 0, $blockId = 0)
    {
        return Gallery::sort($blockId, $pageId);
    }

    public function postUpdate($pageId = 0, $blockId = 0)
    {
        return Gallery::update($blockId, $pageId);
    }

    public function deleteUpdate($pageId = 0, $blockId = 0)
    {
        $file = Request::input('file');
        if (empty($file)) {
            $file = $pageId;
            $pageId = 0;
        }
        return Gallery::delete($blockId, $pageId, $file);
    }

}