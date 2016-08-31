<?php namespace CoasterCms\Helpers\Admin\Validation\Database;

class DB {

	public $table;

	public function __call($method, $args)
	{
		return $this;
	}

	public static function __callStatic($method, $args)
	{
		return new self;
	}

	public function __get($name)
	{
		return null;
	}

}
