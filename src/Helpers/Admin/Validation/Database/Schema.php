<?php namespace CoasterCms\Helpers\Admin\Validation\Database;

class Schema {

	public static function table($tableName, $cb)
	{
        call_user_func($cb, new Blueprint($tableName));
	}

}
