<?php namespace CoasterCms\Libraries\Blocks;

class Stringwprice extends String_
{
    /**
     * Return stringwprice rendered view
     * @param string $content
     * @param array $options
     * @return string
     */
    public function display($content, $options = [])
    {
        return $this->_renderDisplayView($options, $this->_defaultData($content));
    }

    /**
     * Return stringwprice data
     * @param string $content
     * @param array $options
     * @return \stdClass
     */
    public function data($content, $options = [])
    {
        return $this->_defaultData($content);
    }

    /**
     * Pass valid ata to edit function
     * @param string $content
     * @return string
     */
    public function edit($content)
    {
        return parent::edit($this->_defaultData($content));
    }

    /**
     * Save text/price data
     * @param array $postContent
     * @return static
     */
    public function submit($postContent)
    {
        if ($postContent && (!empty($postContent['text']) || !empty($postContent['price']))) {
            $saveData = $this->_defaultData('');
            $saveData->text = !empty($postContent['text']) ? $postContent['text'] : '';
            $saveData->price = !empty($postContent['price']) ? $postContent['price'] : 0;
            unset($saveData->selected);
        }
        return $this->save(isset($saveData) ? serialize($saveData) : '');
    }

    /**
     * Return valid stringwprice data
     * @param $content
     * @return \stdClass
     */
    protected function _defaultData($content)
    {
        $content = @unserialize($content);
        if (empty($content) || !is_a($content, \stdClass::class)) {
            $content = new \stdClass;
        }
        $content->text = isset($content->text) ? $content->text : '';
        $content->text = isset($content->selected) ? $content->selected : $content->text; // text was saved to 'selected' before bugfix
        $content->price = isset($content->price) ? $content->price : '';
        return $content;
    }

    /**
     * Add text and price data to search
     * @param null|string $content
     * @return null|string
     */
    public function generateSearchText($content)
    {
        $content = $this->_defaultData($content);
        $searchText = $this->_generateSearchText($content->text, $content->price);
        return parent::generateSearchText($searchText);
    }

}
