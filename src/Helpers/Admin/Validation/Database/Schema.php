<?php namespace CoasterCms\Helpers\Admin\Validation\Database;

class Schema {

	public static function table($tableName, $cb)
	{
        call_user_func($cb, new Blueprint($tableName));
	}

    public static function rename($from, $to)
    {
        Column::$instances[$to] = Column::$instances[$from];
        unset(Column::$instances[$from]);
    }

    public static function hasColumn()
    {
        return true;
    }

}
