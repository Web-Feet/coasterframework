<?php namespace CoasterCms\Http\Controllers\AdminControllers;

use CoasterCms\Http\Controllers\AdminController as Controller;
use CoasterCms\Libraries\Blocks\Gallery;
use CoasterCms\Models\AdminLog;
use CoasterCms\Models\Block;
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
        $this->layoutData['content'] = $this->_getGalleryBlock($pageId, $blockId)->editPage();
    }

    public function getUpdate($pageId = 0, $blockId = 0)
    {
        return $this->postUpdate($pageId, $blockId);
    }

    // ajax updates

    public function postCaption($pageId = 0, $blockId = 0)
    {
        return $this->_getGalleryBlock($pageId, $blockId)->submitCaption();
    }

    public function postSort($pageId = 0, $blockId = 0)
    {
        return $this->_getGalleryBlock($pageId, $blockId)->submitSort();
    }

    public function postUpdate($pageId = 0, $blockId = 0)
    {
        return $this->_getGalleryBlock($pageId, $blockId)->runHandler();
    }

    public function deleteUpdate($pageId = 0, $blockId = 0)
    {
        return $this->_getGalleryBlock($pageId, $blockId)->submitDelete(Request::input('file'));
    }

    /**
     * @param int $blockId
     * @param int $pageId
     * @return \CoasterCms\Libraries\Blocks\Gallery
     * @throws \Exception
     */
    protected function _getGalleryBlock($pageId, $blockId)
    {
        $galleryBlock = Block::preload($blockId)->setPageId($pageId)->getTypeObject();
        if (is_a($galleryBlock, Gallery::class)) {
            return $galleryBlock;
        } else {
            throw new \Exception('Not a gallery block!');
        }
    }

}