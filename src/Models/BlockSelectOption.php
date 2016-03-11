<?php namespace CoasterCms\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

Class BlockSelectOption extends Eloquent
{
    protected $table = 'block_selectopts';

    public static function import($block_id, $inputOptions)
    {
        if (!empty($inputOptions)) {

            $databaseOptions = [];
            $options = BlockSelectOption::where('block_id', '=', $block_id)->get();
            if (!$options->isEmpty()) {
                foreach ($options as $option) {
                    $databaseOptions[$option->value] = $option;
                }
            }

            $toAdd = array_diff_key($inputOptions, $databaseOptions);
            $toUpdate = array_intersect_key($inputOptions, $databaseOptions);
            $toDelete = array_diff_key($databaseOptions, $inputOptions);

            if (!empty($toDelete)) {
                BlockSelectOption::where('block_id', '=', $block_id)->whereIn('value', array_keys($toDelete))->delete();
            }

            if (!empty($toAdd)) {
                foreach ($toAdd as $value => $option) {
                    $newBlockSelectOption = new BlockSelectOption;
                    $newBlockSelectOption->block_id = $block_id;
                    $newBlockSelectOption->value = $value;
                    $newBlockSelectOption->option = $option;
                    $newBlockSelectOption->save();
                }
            }

            if (!empty($toUpdate)) {
                foreach ($toUpdate as $value => $option) {
                    if ($option != $databaseOptions[$value]->option) {
                        $databaseOptions[$value]->option = $option;
                        $databaseOptions[$value]->save();
                    }
                }
            }
        }
    }

}