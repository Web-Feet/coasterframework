<?php namespace CoasterCms\Libraries\Blocks;

use CoasterCms\Helpers\Cms\Page\Path;
use CoasterCms\Models\Page;
use Request;
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
        $this->_editExtraViewData['targetOptions'] = [0 => 'Target: Same Tab', '_blank' => 'Target: New Tab'];
        $this->_editExtraViewData['selectedPage'] = ($count > 0) ? $link : 0;
        $this->_editExtraViewData['pageList'] = [0 => 'Custom Link: '] + Page::get_page_list();
        return parent::edit($content);
    }

    public function submit($postDataKey = '')
    {
        if ($linkBlocks = Request::input($postDataKey . $this->_editClass)) {
            foreach ($linkBlocks as $blockId => $linkData) {
                $content = [];
                if (!empty($linkData['internal'])) {
                    $content['link'] = 'internal://' . $linkData['internal'];
                } elseif (!empty($linkData['custom'])) {
                    $content['link'] = $linkData['custom'];
                } else {
                    $content['link'] = '';
                }
                $content['target'] = !empty($content['target']) ? $content['target'] : '';
                $this->save(empty($content['link']) ? '' : serialize($content));
            }
        }
    }

    protected function _defaultData($content)
    {
        try {
            $content = unserialize($content);
        } catch (\Exception $e) {}
        $content = is_array($content) ? $content : [];
        $content = $content + ['link' => '', 'target' => ''];
        return $content;
    }

    public static function exportFiles($block, $block_data)
    {
        $doc = [];
        If (!empty($block_data)) {
            $block_data = unserialize($block_data);
            if (!empty($block_data['link'])) {
                if (strpos($block_data['link'], '/') === 0 || strpos($block_data['link'], URL::to('/')) === 0) {
                    $doc[] = str_replace(URL::to('/'), '', $block_data['link']);
                }
            }
        }
        return $doc;
    }

}