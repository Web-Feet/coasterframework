<?php namespace CoasterCms\Models;

use CoasterCms\Libraries\Traits\DataPreLoad;
use Eloquent;

Class BlockRepeater extends Eloquent
{
    use DataPreLoad;

    protected $table = 'block_repeaters';

    protected static function _preloadByColumn()
    {
        return ['block_id'];
    }

    public static function getRepeaterBlocks($blockId)
    {
        $repeaterBlocks = [];
        if ($blockRepeater = BlockRepeater::where('block_id', '=', $blockId)->first()) {
            foreach (explode(",", $blockRepeater->blocks) as $repeaterBlockId) {
                $repeaterBlocks[$repeaterBlockId] = Block::preload($repeaterBlockId);
            }
        }
        return $repeaterBlocks;
    }

}