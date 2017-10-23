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
        $blockRepeater = static::preload($blockId);
        if ($blockRepeater->exists) {
            foreach (explode(",", $blockRepeater->blocks) as $repeaterBlockId) {
                $repeaterBlocks[$repeaterBlockId] = Block::preloadClone($repeaterBlockId);
            }
        }
        return $repeaterBlocks;
    }

    public static function addChildBlockIds($blockIds)
    {
        $blockRepeaterBlockIds = [];
        foreach ($blockIds as $blockId) {
            $blockRepeater = static::preload($blockId);
            if ($blockRepeater->exists) {
                $blockRepeaterChildren = explode(',', $blockRepeater->blocks);
                if (($key = array_search($blockId, $blockRepeaterChildren)) !== false) {
                    unset($blockRepeaterChildren[$key]);
                }
                $blockRepeaterBlockIds = array_merge(
                    $blockRepeaterBlockIds,
                    static::addChildBlockIds($blockRepeaterChildren),
                    [$blockId]
                );
            }
        }
        return array_unique($blockRepeaterBlockIds);
    }

}
