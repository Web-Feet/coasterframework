<?php namespace CoasterCms\Libraries\Blocks;

class Stringwprice extends String_
{

    public function display($content)
    {
        return $this->_defaultData($content);
    }

    public function edit($content)
    {
        return parent::edit($this->_defaultData($content));
    }

    public function save($content)
    {
        if ($content && (!empty($content['text']) || !empty($content['price']))) {
            $saveData = new \stdClass;
            $saveData->selected = !empty($content['text']) ? $content['text'] : '';
            $saveData->price = !empty($content['price']) ? $content['price'] : 0;
        }
        return parent::save(isset($saveData) ? serialize($saveData) : '');
    }

    protected function _defaultData($content)
    {
        $content = @unserialize($content);
        if (empty($content) || !is_a($content, \stdClass::class)) {
            $content = new \stdClass;
        }
        $content->text = !empty($content->text) ? $content->text : '';
        $content->price = !empty($content->price) ? $content->price : 0;
        return $content;
    }

    public function generateSearchText($content)
    {
        $content = $this->_defaultData($content);
        return $this->_generateSearchText($content->text, $content->price);
    }

}