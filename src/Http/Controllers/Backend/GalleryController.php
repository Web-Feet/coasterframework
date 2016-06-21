<?php namespace CoasterCms\Http\Controllers\Backend;

use CoasterCms\Http\Controllers\AdminController as Controller;
use CoasterCms\Libraries\Blocks\Gallery;
use CoasterCms\Models\Page;
use CoasterCms\Models\PageLang;
use CoasterCms\Models\Template;
use Redirect;
use Request;
use URL;
use View;

class GalleryController extends Controller
{

    public function get_list($page_id = 0)
    {
        $page = Page::find($page_id);
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
                return Redirect::to(URL::to(config('coaster::admin.url') . '/gallery/edit/' . $page_id . '/' . $gallery_blocks[0]->id));
            }
            $page_lang_data = PageLang::preload($page_id);
            if (!empty($page_lang_data)) {
                $name = $page_lang_data->name;
                if ($page->parent != 0) {
                    $parent_lang_data = PageLang::preload($page->parent);
                    $name = $parent_lang_data->name . " / " . $name;
                }
            } else {
                $name = '';
            }
            $this->layoutData['content'] = View::make('coaster::pages.gallery.list', array('page_name' => $name, 'page_id' => $page_id, 'galleries' => $gallery_blocks));
        } else {
            $this->layoutData['content'] = 'No Galleries Found';
        }
    }

    // main actions

    public function getEdit($page_id = 0, $block_id = 0)
    {
        $this->layoutData['content'] = Gallery::page($block_id, $page_id);
    }

    public function get_update($page_id = 0, $block_id = 0)
    {
        return Gallery::run_handler($block_id, $page_id);
    }

    // ajax updates

    public function post_caption($page_id = 0, $block_id = 0)
    {
        return Gallery::caption($block_id, $page_id);
    }

    public function post_sort($page_id = 0, $block_id = 0)
    {
        return Gallery::sort($block_id, $page_id);
    }

    public function post_update($page_id = 0, $block_id = 0)
    {
        return Gallery::update($block_id, $page_id);
    }

    public function delete_update($page_id = 0, $block_id = 0)
    {
        $file = Request::input('file');
        if (empty($file)) {
            $file = $page_id;
            $page_id = 0;
        }
        return Gallery::delete($block_id, $page_id, $file);
    }

}