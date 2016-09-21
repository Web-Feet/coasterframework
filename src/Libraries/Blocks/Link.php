<?php namespace CoasterCms\Libraries\Blocks;

use CoasterCms\Helpers\Cms\Page\Path;
use CoasterCms\Models\Page;
use URL;

class Link extends String_
{

    public function display($content, $options = [])
    {
        $content = $this->_defaultData($content);
        $target = $content['target'] ? ' target=\"'.$content['target'].'"' : '';
        $link = str_replace('internal://', '', $content['link'], $count);
        return (($count > 0) ? Path::getFullUrl($link) : $link) . $target;
    }

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

    public function save($content)
    {
        $linkData = [];
        if (!empty($content['internal'])) {
            $linkData['link'] = 'internal://' . $content['internal'];
        } elseif (!empty($content['custom'])) {
            $linkData['link'] = $content['custom'];
        } else {
            $linkData['link'] = '';
        }
        $linkData['target'] = !empty($linkData['target']) ? $linkData['target'] : '';
        return parent::save(empty($linkData['link']) ? '' : serialize($linkData));
    }

    public function generateSearchText($content)
    {
        $content = $this->_defaultData($content);
        $content['link'] = str_replace('internal://', '', $content['link'], $count);
        if ($count > 0) {
            $paths = Path::getById($content['link']);
            $content['link'] = $paths->exists ? $paths->name : '';
        }
        return $this->_generateSearchText($content['link']);
    }

    protected function _defaultData($content)
    {
        $content = @unserialize($content);
        if (empty($content) || !is_array($content)) {
            $content = [];
        }
        $content = $content + ['link' => '', 'target' => ''];
        return $content;
    }

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