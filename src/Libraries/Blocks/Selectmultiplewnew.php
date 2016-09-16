<?php namespace CoasterCms\Libraries\Blocks;

use CoasterCms\Models\BlockSelectOption;

class Selectmultiplewnew extends Selectmultiple
{

    public function save($content)
    {
        $content['select'] = !empty($content['select']) ? $content['select'] : [];

        if ($content['custom']) {
            $newOptions = explode(',', $content['custom']);
            $optionsArr = [];
            $options = BlockSelectOption::where('block_id', '=', $this->_block->id)->get();
            if (!$options->isEmpty()) {
                foreach ($options as $option) {
                    $optionsArr[$option->value] = $option->option;
                }
            }
            foreach ($newOptions as $newOption) {
                if ($newOption && empty($optionsArr[$newOption])) {
                    $optionsArr[$newOption] = $newOption;
                }
            }
            BlockSelectOption::import($this->_block->id, $optionsArr);
            $content['select'] = array_merge($content['select'], $newOptions);
        }

        $this->save($content['select']);
    }

}
