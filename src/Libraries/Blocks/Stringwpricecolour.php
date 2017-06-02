<?php namespace CoasterCms\Libraries\Blocks;

use CoasterCms\Models\BlockSelectOption;

class Stringwpricecolour extends String_
{
    /**
     * Return stringwpricecolour rendered view
     * @param string $content
     * @param array $options
     * @return string
     */
    public function display($content, $options = [])
    {
        return $this->_renderDisplayView($options, $this->_defaultData($content));
    }

    /**
     * Return stringwpricecolour data
     * @param string $content
     * @param array $options
     * @return \stdClass
     */
    public function data($content, $options = [])
    {
        return $this->_defaultData($content);
    }

    /**
     * Get colour option to select with of fields
     * @param string $content
     * @return string
     */
    public function edit($content)
    {
        $this->_editViewData['selectOptions'] = array('none' => 'No Colour');
        $selectOptions = BlockSelectOption::where('block_id', '=', $this->_block->id)->get();
        foreach ($selectOptions as $selectOption) {
            $this->_editViewData['selectOptions'][$selectOption->value] = $selectOption->option;
        }
        $this->_editViewData['selectClass'] = 'select_colour';
        return parent::edit($this->_defaultData($content));
    }

    /**
     * Save text/price/colour data
     * @param array $postContent
     * @return static
     */
    public function submit($postContent)
    {
        if ($postContent && (!empty($postContent['text']) || !empty($postContent['colour']) || !empty($postContent['price']))) {
            $saveData = $this->_defaultData('');
            $saveData->selected = !empty($postContent['text']) ? $postContent['text'] : '';
            $saveData->price = !empty($postContent['price']) ? $postContent['price'] : 0;
            $saveData->colour = !empty($postContent['colour']) ? $postContent['colour'] : '';
        }
        return $this->save(isset($saveData) ? serialize($saveData) : '');
    }

    /**
     * Return valid stringwpricecolour data
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
        $content->price = isset($content->price) ? $content->price : '';
        $content->colour = isset($content->colour) ? $content->colour : '';
        return $content;
    }

    /**
     * Add text, price and colour data to search
     * @param null|string $content
     * @return null|string
     */
    public function generateSearchText($content)
    {
        $content = $this->_defaultData($content);
        $searchText = $this->_generateSearchText($content->text, $content->price, $content->colour);
        return parent::generateSearchText($searchText);
    }

}