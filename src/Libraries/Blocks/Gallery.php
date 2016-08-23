<?php namespace CoasterCms\Libraries\Blocks;

use Auth;
use CoasterCms\Helpers\Cms\BlockManager;
use CoasterCms\Helpers\Admin\GalleryUploadHandler;
use CoasterCms\Libraries\Builder\PageBuilder;
use CoasterCms\Models\AdminLog;
use CoasterCms\Models\Block;
use CoasterCms\Models\Page;
use CoasterCms\Models\PageGroup;
use CoasterCms\Models\PageLang;
use Request;
use URL;
use View;

class Gallery extends _Base
{

    public static function display($block, $block_data, $options = [])
    {
        $images = array();
        $gallery_data = @unserialize($block_data);
        if (!empty($gallery_data)) {
            uasort($gallery_data, array('\CoasterCms\Helpers\Admin\GAlleryUploadHandler', 'order_items'));
            foreach ($gallery_data as $image => $image_data) {
                $data = new \stdClass;
                $data->caption = $image_data->caption;
                $data->file = '/uploads/system/gallery/' . $block->name . $image_data->path . $image;
                array_push($images, $data);
            }
        }
        $options['view'] = !empty($options['view']) ? $options['view'] : 'default';

        $galleryViews = 'themes.' . PageBuilder::getData('theme') . '.blocks.gallery.';

        if (empty($options['view']) && View::exists($galleryViews . $block->name)) {
            return View::make($galleryViews . $block->name, ['images' => $images])->render();
        } elseif (View::exists($galleryViews . $options['view'])) {
            return View::make($galleryViews . $options['view'], ['images' => $images])->render();
        } else {
            return 'Gallery template not found';
        }
    }

    public static function edit($block, $block_data, $page_id = 0, $parent_repeater = null)
    {
        $gallery_data = new \stdClass;
        $gallery_data->pictures = !empty($block_data) ? count(@unserialize($block_data)) : 0;
        return $gallery_data;
    }

    public static function exportFiles($block, $block_data)
    {
        $images = [];
        $gallery_data = @unserialize($block_data);
        if (!empty($gallery_data)) {
            foreach ($gallery_data as $image => $image_data) {
                $images[] = '/uploads/system/gallery/' . $block->name . $image_data->path . $image;
            }
        }
        return $images;
    }

    // gallery specific functions below

    public static function page($block_id, $page_id)
    {
        $page = Page::preload($page_id);
        $block_data = Block::find($block_id);
        if (empty($block_data) || $block_data->type != 'gallery')
            return null;
        else {
            if ($page->exists) {
                $page_lang_data = PageLang::preload($page_id);
                $name = $page_lang_data->name;
                if ($page->groups) {
                    $name = $page->groupNames() . " / " . $name;
                } elseif ($page->parent > 0) {
                    $parentPage = Page::preload($page->parent);
                    $parent_lang_data = PageLang::preload($parentPage->id);
                    $name = $parent_lang_data->name . " / " . $name;
                }
                $name .= " - " . $block_data->label;
                $page_info = new \stdClass;
                $page_info->id = $page_id;
                $page_info->name = $page_lang_data->name;
            } else {
                $page_info = null;
                $name = $block_data->label;
            }
            return View::make('coaster::pages.gallery', array('name' => $name, 'page' => $page_info, 'can_delete' => Auth::action('gallery.delete', ['page_id' => $page_id]), 'can_edit_caption' => Auth::action('gallery.caption', ['page_id' => $page_id])));
        }
    }

    public static function sort($block_id, $page_id)
    {
        $order = 1;
        $gallery_data_s = BlockManager::get_block($block_id, $page_id);
        if (!empty($gallery_data_s)) {
            $gallery_data = unserialize($gallery_data_s);
            foreach (Request::input('arr') as $image) {
                $gallery_data[$image]->order = $order++;
            }
            BlockManager::update_block($block_id, serialize($gallery_data), $page_id, null, 2);
            return 1;
        } else {
            return 0;
        }
    }

    public static function delete($block_id, $page_id, $file)
    {
        AdminLog::new_log('Removed ' . $file . ' from Gallery (ID ' . $block_id . ')');
        $block_data = Block::find($block_id);
        if (empty($block_data) || $block_data->type != 'gallery')
            return 0;
        else {
            $gallery_data_s = BlockManager::get_block($block_id, $page_id);
            if (!empty($gallery_data_s)) {
                $gallery_data = unserialize($gallery_data_s);
                if (isset($gallery_data[$file])) {
                    $path = $gallery_data[$file]->path;
                    unset($gallery_data[$file]);
                    BlockManager::update_block($block_id, serialize($gallery_data), $page_id, null, 2);
                } else {
                    $path = '/' . $page_id . '/';
                }
                if (file_exists(public_path() . '/uploads/system/gallery/' . $block_data->name . $path . 'thumbnail/' . $file))
                    unlink(public_path() . '/uploads/system/gallery/' . $block_data->name . $path . 'thumbnail/' . $file);
                if (file_exists(public_path() . '/uploads/system/gallery/' . $block_data->name . $path . $file))
                    unlink(public_path() . '/uploads/system/gallery/' . $block_data->name . $path . $file);
            }
            return 1;
        }
    }

    public static function update($block_id, $page_id)
    {
        AdminLog::new_log('Uploaded files to Gallery (ID ' . $block_id . ')');
        return self::run_handler($block_id, $page_id);
    }

    public static function caption($block_id, $page_id)
    {
        $gallery_data_s = BlockManager::get_block($block_id, $page_id);
        if (!empty($gallery_data_s)) {
            $gallery_data = unserialize($gallery_data_s);
            if (!empty($gallery_data[Request::input('file_data')])) {
                $gallery_data[Request::input('file_data')]->caption = Request::input('caption');
                BlockManager::update_block($block_id, serialize($gallery_data), $page_id, null, 2);
            }
        }
        return 1;
    }

    public static function run_handler($block_id, $page_id)
    {
        $block_data = Block::find($block_id);
        if (empty($block_data) || $block_data->type != 'gallery')
            return 0;
        else {
            $gallery_data_s = BlockManager::get_block($block_id, $page_id);
            $selected_data = array();
            if (!empty($gallery_data_s)) {
                $gallery_data = unserialize($gallery_data_s);
                foreach ($gallery_data as $image => $image_data) {
                    $selected_data[$image] = new \stdClass;
                    $selected_data[$image]->order = $image_data->order;
                    $selected_data[$image]->caption = $image_data->caption;
                }
            }
            $upload_handler = new GalleryUploadHandler(
                array(
                    'selected_data' => $selected_data,
                    'script_url' => Request::url(),
                    'max_file_size' => 2000000, //2MB   (will also error if over php.ini post_max_size)
                    'accept_file_types' => '/\.(gif|jpe?g|png)$/i',
                    'upload_dir' => public_path() . '/uploads/system/gallery/' . $block_data->name . '/' . $page_id . '/',
                    'upload_url' => URL::to('/uploads/system/gallery/' . $block_data->name . '/' . $page_id) . '/')
            );
            if (!empty($upload_handler->name)) {
                $gallery_data_s = BlockManager::get_block($block_id, $page_id);
                if (!empty($gallery_data_s)) {
                    $gallery_data = unserialize($gallery_data_s);
                    $order = 0;
                    foreach ($gallery_data as $image_data) {
                        if ($order < $image_data->order)
                            $order = $image_data->order;
                    }
                } else {
                    $gallery_data = array();
                    $order = 0;
                }
                $gallery_data[$upload_handler->name] = new \stdClass;
                $gallery_data[$upload_handler->name]->caption = '';
                $gallery_data[$upload_handler->name]->order = $order + 1;
                $gallery_data[$upload_handler->name]->path = '/' . $page_id . '/';
                BlockManager::update_block($block_id, serialize($gallery_data), $page_id, null, 2);
            }
            return '';
        }
    }

}