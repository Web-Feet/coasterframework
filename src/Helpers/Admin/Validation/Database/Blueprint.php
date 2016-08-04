<?php namespace CoasterCms\Helpers\Admin\Validation\Database;

class Blueprint {

	public $tableName;

    public function __construct($tableName)
    {
        $this->tableName = $tableName;
    }

    public function create()
	{
        
	}

	public function timestamps()
	{
        new Column($this->tableName, 'created_at', ['Type' => 'timestamp', 'Default' => 'CURRENT_TIMESTAMP']);
        new Column($this->tableName, 'updated_at', ['Type' => 'timestamp', 'Default' => 'CURRENT_TIMESTAMP', 'Extra' => 'on update CURRENT_TIMESTAMP']);
	}

	public function rememberToken()
	{
        new Column($this->tableName, 'remember_token', ['Type' => 'remember_token', 'Null' => 'YES']);
	}

	public function __call($type, $args)
	{
        if ($type == 'dropColumn') {
            unset(Column::$instances[$this->tableName][$args[0]]);
            return null;
        }
        $details = ['Type' => $type];
        if ($type == 'enum') {
            $details['Type'] .= '(\''.implode('\',\'', $args[1]).'\')';
        }
        if ($type == 'increments') {
            $details['Type'] = 'integer';
            $details['Extra'] = 'auto_increment';
        }
        $column = new Column($this->tableName, $args[0], $details);
        if (stripos($type, 'increments') !== false) {
            $column->primary()->unsigned();
        }
        return $column;
	}

}
