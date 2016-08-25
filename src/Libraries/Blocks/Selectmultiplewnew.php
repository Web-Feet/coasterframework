<?php namespace CoasterCms\Libraries\Blocks;

use CoasterCms\Helpers\Cms\Theme\BlockManager;
use CoasterCms\Models\BlockSelectOption;
use Request;

class Selectmultiplewnew extends Selectmultiple
{

    public static $blocks_key = 'blockSmN';

    public static function submit($page_id, $blocks_key, $repeater_info = null)
    {
        $selectedOptionsByBlockId = [];

        $customUsers = Request::input($blocks_key) ?: [];
        foreach ($customUsers as $blockId => $selectedOptions) {
            $selectedOptionsByBlockId[$blockId] = $selectedOptions;
        }

        $selectOptions = Request::input($blocks_key . 'Custom') ?: [];
        foreach ($selectOptions as $blockId => $newOptions) {
            if ($newOptions) {
                $newOptions = explode(',', $newOptions);
                $optionsArr = [];
                $options = BlockSelectOption::where('block_id', '=', $blockId)->get();
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
                BlockSelectOption::import($blockId, $optionsArr);
                $selectedOptionsByBlockId[$blockId] = array_merge(!empty($selectedOptionsByBlockId[$blockId]) ? $selectedOptionsByBlockId[$blockId] : [], $newOptions);
            }
        }

        foreach ($selectedOptionsByBlockId as $blockId => $selectedOptions) {
            BlockManager::update_block($blockId, self::save($selectedOptions), $page_id, $repeater_info);
        }

        if ($checkForEmpty= Request::input($blocks_key . '_exists') ?: []) {
            foreach ($checkForEmpty as $blockId => $v) {
                if (empty($selectedOptionsByBlockId[$blockId])) {
                    BlockManager::update_block($blockId, '', $page_id, $repeater_info);
                }
            }
        }

    }

}