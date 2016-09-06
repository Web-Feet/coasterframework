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

}