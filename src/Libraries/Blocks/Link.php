<?php namespace CoasterCms\Libraries\Blocks;

use CoasterCms\Helpers\Cms\Page\Path;
use CoasterCms\Models\Page;
use URL;

class Link extends String_
{
    /**
     * Display image link (target attribute appended to end or link if exists)
     * @param string $content
     * @param array $options
     * @return string
     */
    public function display($content, $options = [])
    {
        $content = $this->_defaultData($content);
        $target = $content['target'] ? ' target=\"'.$content['target'].'"' : '';
        $link = str_replace('internal://', '', $content['link'], $count);
        return (($count > 0) ? Path::getFullUrl($link) : $link) . $target;
    }

    /**
     * Edit link settings
     * @param string $content
     * @return string
     */
    public function edit($content)
    {
        $content = $this->_defaultData($content);
        $link = str_replace('internal://', '', $content['link'], $count);
        $content['link'] = ($count > 0) ? '' : $content['link'];
        $this->_editViewData['targetOptions'] = [0 => 'Target: Same Tab', '_blank' => 'Target: New Tab'];
        $this->_editViewData['selectedPage'] = ($count > 0) ? $link : 0;
        $this->_editViewData['pageList'] = [0 => 'Custom Link: '] + Page::get_page_list();
        return parent::edit($content);
    }

    /**
     * Update link settings
     * @param array $postContent
     * @return static
     */
    public function submit($postContent)
    {
        $linkData = [];
        if (!empty($postContent['internal'])) {
            $linkData['link'] = 'internal://' . $postContent['internal'];
        } elseif (!empty($postContent['custom'])) {
            $linkData['link'] = $postContent['custom'];
        } else {
            $linkData['link'] = '';
        }
        $linkData['target'] = !empty($postContent['target']) ? $postContent['target'] : '';
        return $this->save(empty($linkData['link']) ? '' : serialize($linkData));
    }

    /**
     * Save link in search text (without the target attribute)
     * @param null|string $content
     * @return null|string
     */
    public function generateSearchText($content)
    {
        $content = $this->_defaultData($content);
        $content['link'] = str_replace('internal://', '', $content['link'], $count);
        if ($count > 0) {
            $paths = Path::getById($content['link']);
            $content['link'] = $paths->exists ? $paths->name : '';
        }
        return parent::generateSearchText($content['link']);
    }

    /**
     * Return valid link data
     * @param string $content
     * @return array
     */
    protected function _defaultData($content)
    {
        $content = @unserialize($content);
        if (empty($content) || !is_array($content)) {
            $content = [];
        }
        $content = $content + ['link' => '', 'target' => ''];
        return $content;
    }

    /**
     * Return link documents for exporting with theme data
     * @param string $content
     * @return array
     */
    public function exportFiles($content)
    {
        $content = $this->_defaultData($content);
        if (!empty($content['link']) && (strpos($content['link'], '/') === 0 || strpos($content['link'], URL::to('/')) === 0)) {
            return [str_replace(URL::to('/'), '', $content['link'])];
        } else {
            return [];
        }
    }

}
