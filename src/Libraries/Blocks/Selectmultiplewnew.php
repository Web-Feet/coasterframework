<?php namespace CoasterCms\Libraries\Blocks;

use CoasterCms\Models\BlockSelectOption;

class Selectmultiplewnew extends Selectmultiple
{
    /**
     * Allow custom options to be added (comma separated)
     * @param array $postContent
     * @return static
     */
    public function submit($postContent)
    {
        $postContent['select'] = !empty($postContent['select']) ? $postContent['select'] : [];

        if ($postContent['custom']) {
            $newOptions = explode(',', $postContent['custom']);
            $optionsArr = [];
            $options = BlockSelectOption::where('block_id', '=', $this->_block->id)->get();
            if (!$options->isEmpty()) {
                foreach ($options as $option) {
                    $optionsArr[$option->value] = $option->option;
                }
            }
            foreach ($newOptions as $newOption) {
                $newOption = trim($newOption);
                if ($newOption && empty($optionsArr[$newOption])) {
                    $optionsArr[$newOption] = $newOption;
                }
            }
            BlockSelectOption::import($this->_block->id, $optionsArr);
            $postContent['select'] = array_merge($postContent['select'], $newOptions);
        }

        return $this->save(!empty($postContent['select']) ? serialize($postContent['select']) : '');
    }

}
