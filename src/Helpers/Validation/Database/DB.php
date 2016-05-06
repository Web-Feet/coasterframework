<?php namespace CoasterCms\Helpers\Validation\Database;

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

}
