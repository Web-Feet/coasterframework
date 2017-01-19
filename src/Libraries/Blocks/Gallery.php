<?php namespace CoasterCms\Libraries\Blocks;

use Auth;
use CoasterCms\Helpers\Cms\Page\Path;
use CoasterCms\Helpers\Admin\GalleryUploadHandler;
use CoasterCms\Models\AdminLog;
use CoasterCms\Models\Page;
use CoasterCms\Models\PageVersion;
use Request;
use URL;
use View;

class Gallery extends String_
{
    /**
     * @param string $content
     * @param array $options
     * @return string
     */
    public function display($content, $options = [])
    {
        if ($galleryData = $this->_defaultData($content)) {
            uasort($galleryData, [GalleryUploadHandler::class, 'order_items']);
            foreach ($galleryData as $image => $imageData) {
                $galleryData[$image]->file = '/uploads/system/gallery/' . $this->_block->name . $imageData->path . $image;
            }
        }

        return $this->_renderDisplayView($options, ['images' => $galleryData], 'image');
    }

    /**
     * @param array $options
     * @return string
     */
    public function displayDummy($options)
    {
        $image = new \stdClass;
        $image->caption = '';
        $image->order = '';
        $image->path = '';
        $image->file = '';
        return $this->_renderDisplayView($options, ['images' => [$image]], 'image');
    }

    /**
     * @param string $content
     * @return string
     */
    public function edit($content)
    {
        return parent::edit($this->_defaultData($content));
    }

    /**
     * Return valid form data
     * @param $content
     * @return \stdClass
     */
    protected function _defaultData($content)
    {
        $content = @unserialize($content);
        return (empty($content) || !is_array($content)) ? [] : $content;
    }

    /**
     * Export gallery images
     * @param string $content
     * @return array
     */
    public function exportFiles($content)
    {
        $images = [];
        if ($galleryData = $this->_defaultData($content)) {
            foreach ($galleryData as $image => $imageData) {
                $images[] = '/uploads/system/gallery/' . $this->_block->name . $imageData->path . $image;
            }
        }
        return $images;
    }

    // gallery specific functions below

    /**
     * Display full edit page for gallery
     * @return \Illuminate\Contracts\View\View|string
     */
    public function editPage()
    {
        $page = Page::preload($this->_block->getPageId());
        if ($page->exists) {
            $paths = Path::getById($page->id);
            return View::make('coaster::pages.gallery', ['paths' => $paths, '_block' => $this->_block, 'can_delete' => Auth::action('gallery.delete', ['page_id' => $page->id]), 'can_edit_caption' => Auth::action('gallery.caption', ['page_id' => $page->id])]);
        } else {
            return 'page not found';
        }
    }

    /**
     * Update sort values of images
     * @return int
     */
    public function submitSort()
    {
        $order = 1;
        $galleryData = $this->_defaultData($this->_block->getContent());
        foreach (Request::input('arr') as $image) {
            if (!isset($galleryData[$image])) {
                return 0;
            }
            $galleryData[$image]->order = $order++;
        }
        $this->_block->updateContent(serialize($galleryData));
        if ($this->_block->getPageId() && !config('coaster::admin.publishing')) { // update live page if no publishing
            PageVersion::latest_version($this->_block->getPageId(), true)->publish();
        }
        return 1;
    }

    /**
     * Update image caption in gallery
     * @return int
     */
    public function submitCaption()
    {
        $galleryData = $this->_defaultData($this->_block->getContent());
        if (!empty($galleryData[Request::input('file_data')])) {
            $galleryData[Request::input('file_data')]->caption = Request::input('caption');
            $this->_block->updateContent(serialize($galleryData));
            if ($this->_block->getPageId() && !config('coaster::admin.publishing')) { // update live page if no publishing
                PageVersion::latest_version($this->_block->getPageId(), true)->publish();
            }
            return 1;
        }
        return 0;
    }

    /**
     * Delete image from gallery
     * @param string $file
     * @return int
     */
    public function submitDelete($file)
    {
        AdminLog::new_log('Removed ' . $file . ' from Gallery (ID ' . $this->_block->id . ')');
        $galleryData = $this->_defaultData($this->_block->getContent());
        $path = '/' . $this->_block->getPageId() . '/';
        if (isset($galleryData[$file])) {
            $path = $galleryData[$file]->path;
            unset($galleryData[$file]);
            $this->_block->updateContent(serialize($galleryData));
        }
        $fullDir = public_path() . '/uploads/system/gallery/' . $this->_block->name . $path;
        if (file_exists($fullDir . 'thumbnail/' . $file)) {
            unlink($fullDir . 'thumbnail/' . $file);
        }
        if (file_exists($fullDir . $file)) {
            unlink($fullDir . $file);
        }
        return 1;
    }

    /**
     * Return upload images, handler also uploads image if in request
     * @return string
     */
    public function runHandler()
    {
        $currentData = $this->_defaultData($this->_block->getContent());
        $uploadHandler = new GalleryUploadHandler([
            'print_response' => false,
            'selected_data' => $currentData,
            'script_url' => Request::url(),
            'max_file_size' => 2000000, //2MB   (will also error if over php.ini post_max_size)
            'accept_file_types' => '/\.(gif|jpe?g|png)$/i',
            'upload_dir' => public_path() . '/uploads/system/gallery/' . $this->_block->name . '/' . $this->_block->getPageId() . '/',
            'upload_url' => URL::to('/uploads/system/gallery/' . $this->_block->name . '/' . $this->_block->getPageId()) . '/'
        ]);
        if (!empty($uploadHandler->name)) {
            $order = 0;
            foreach ($currentData as $imageData) {
                if ($order < $imageData->order) {
                    $order = $imageData->order;
                }
            }
            AdminLog::new_log('Uploaded files to Gallery (ID ' . $this->_block->id . ')');
            $currentData[$uploadHandler->name] = new \stdClass;
            $currentData[$uploadHandler->name]->caption = '';
            $currentData[$uploadHandler->name]->order = $order + 1;
            $currentData[$uploadHandler->name]->path = '/' . $this->_block->getPageId() . '/';
            $this->_block->updateContent(serialize($currentData));
        }
        if ($this->_block->getPageId() && !config('coaster::admin.publishing')) { // update live page if no publishing
            PageVersion::latest_version($this->_block->getPageId(), true)->publish();
        }
        return $uploadHandler->get_response();
    }

}