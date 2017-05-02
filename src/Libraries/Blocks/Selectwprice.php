<?php namespace CoasterCms\Libraries\Blocks;

use CoasterCms\Models\BlockSelectOption;

class Selectwprice extends String_
{
    /**
     * Return selectwprice rendered view
     * @param string $content
     * @param array $options
     * @return string
     */
    public function display($content, $options = [])
    {
        return $this->_renderDisplayView($options, $this->_defaultData($content));
    }

    /**
     * Return selectwprice data
     * @param string $content
     * @param array $options
     * @return \stdClass
     */
    public function data($content, $options = [])
    {
        return $this->_defaultData($content);
    }

    /**
     * Edit selectwprice data view
     * @param string $content
     * @return string
     */
    public function edit($content)
    {
        $this->_editViewData['selectOptions'] = [];
        $selectOptions = BlockSelectOption::where('block_id', '=', $this->_block->id)->get();
        foreach ($selectOptions as $selectOption) {
            $this->_editViewData['selectOptions'][$selectOption->value] = $selectOption->option;
        }
        if (preg_match('/^#[a-f0-9]{6}$/i', key($selectOptions))) {
            $this->_editViewData['class'] = 'select_colour';
        }
        return parent::edit($this->_defaultData($content));
    }

    /**
     * Update selectwprice data
     * @param array $postContent
     * @return static
     */
    public function submit($postContent)
    {
        if ($postContent && (!empty($postContent['select']) || !empty($postContent['price']))) {
            $saveData = $this->_defaultData('');
            $saveData->selected = !empty($postContent['select']) ? $postContent['select'] : 0;
            $saveData->price = !empty($postContent['price']) ? $postContent['price'] : 0;
        }
        return $this->save(isset($saveData) ? serialize($saveData) : '');
    }

    /**
     * Get valid selectwprice data
     * @param $content
     * @return \stdClass
     */
    protected function _defaultData($content)
    {
        $content = @unserialize($content);
        if (empty($content) || !is_a($content, \stdClass::class)) {
            $content = new \stdClass;
        }
        $content->selected = isset($content->selected) ? $content->selected : '';
        $content->price = isset($content->price) ? $content->price : '';
        return $content;
    }

    /**
     * Add select option and price data to search
     * @param null|string $content
     * @return null|string
     */
    public function generateSearchText($content)
    {
        $content = $this->_defaultData($content);
        $searchText = $this->_generateSearchText($content->selected, $content->price);
        return parent::generateSearchText($searchText);
    }

}