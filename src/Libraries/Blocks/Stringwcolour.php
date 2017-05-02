<?php namespace CoasterCms\Libraries\Blocks;

use CoasterCms\Models\BlockSelectOption;

class Stringwcolour extends String_
{
    /**
     * Return stringwcolour rendered view
     * @param string $content
     * @param array $options
     * @return string
     */
    public function display($content, $options = [])
    {
        return $this->_renderDisplayView($options, $this->_defaultData($content));
    }

    /**
     * Return stringwcolour data
     * @param string $content
     * @param array $options
     * @return \stdClass
     */
    public function data($content, $options = [])
    {
        return $this->_defaultData($content);
    }

    /**
     * Get colour option to select with text box
     * @param string $content
     * @return string
     */
    public function edit($content)
    {
        $this->_editViewData['selectOptions'] = ['none' => 'No Colour'];
        $selectOptions = BlockSelectOption::where('block_id', '=', $this->_block->id)->get();
        foreach ($selectOptions as $selectOption) {
            $this->_editViewData['selectOptions'][$selectOption->value] = $selectOption->option;
        }
        $this->_editViewData['selectClass'] = 'select_colour';
        return parent::edit($this->_defaultData($content));
    }

    public function submit($postContent)
    {
        if ($postContent && (!empty($postContent['text']) || !empty($postContent['colour']))) {
            $saveData = $this->_defaultData('');
            $saveData->selected = !empty($postContent['text']) ? $postContent['text'] : '';
            $saveData->colour = !empty($postContent['colour']) ? $postContent['colour'] : '';
        }
        return $this->save(isset($saveData) ? serialize($saveData) : '');
    }

    /**
     * Return valid stringwcolour data
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
        $content->colour = isset($content->colour) ? $content->colour : '';
        return $content;
    }

    /**
     * Add text and colour data to search
     * @param null|string $content
     * @return null|string
     */
    public function generateSearchText($content)
    {
        $content = $this->_defaultData($content);
        $searchText = $this->_generateSearchText($content->text, $content->colour);
        return parent::generateSearchText($searchText);
    }

}