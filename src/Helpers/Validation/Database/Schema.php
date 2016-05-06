<?php namespace CoasterCms\Helpers\Validation\Database;

class Schema {

	public static function table($tableName, $cb)
	{
        call_user_func($cb, new Blueprint($tableName));
	}

}
