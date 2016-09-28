<?php namespace CoasterCms\Models;

use CoasterCms\Libraries\Traits\DataPreLoad;
use Eloquent;

class BlockCategory extends Eloquent
{
    use DataPreLoad;

    protected $table = 'block_category';


}