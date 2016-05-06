<?php namespace CoasterCms\Helpers\Validation\Database;

class Column {

	public static $instances = [];
	public $tableName;
	public $fieldName;

	public function __construct($tableName, $fieldName, $details = [])
	{
		$this->tableName = $tableName;
		$this->fieldName = $fieldName;
		if (!isset(self::$instances[$this->tableName])) {
			self::$instances[$this->tableName] = [];
		}

        $details['Field'] = $fieldName;

		self::$instances[$this->tableName][$this->fieldName] = array_merge(
            ['Type' => '', 'Null' => 'NO', 'Key' => '', 'Default' => null, 'Extra' => ''],
            $details
        );

        $typeReplaces = [
            'integer' => 'int(11)',
            'string' => 'varchar(255)',
            'mediumText' => 'mediumtext',
            'remember_token' => 'varchar(100)'
        ];
        self::$instances[$this->tableName][$this->fieldName]['Type'] = str_replace(
            array_keys($typeReplaces),
            array_values($typeReplaces),
            self::$instances[$this->tableName][$this->fieldName]['Type']
        );
	}

	public function __call($method, $args)
	{
        if ($method == 'primary') {
            self::$instances[$this->tableName][$this->fieldName]['Key'] = 'PRI';
        }
        if ($method == 'unique') {
            self::$instances[$this->tableName][$this->fieldName]['Key'] = 'UNI';
        }
        if ($method == 'unsigned') {
            preg_match('#int\((\d+)\)#', self::$instances[$this->tableName][$this->fieldName]['Type'], $matches);
            if (!empty($matches[1])) {
                self::$instances[$this->tableName][$this->fieldName]['Type'] = preg_replace('#int\((\d+)\)#', 'int('.(((int) $matches[1])-1).') unsigned', self::$instances[$this->tableName][$this->fieldName]['Type']);
            }
        }
		if ($method == 'default' && $args[0] !== null) {
			self::$instances[$this->tableName][$this->fieldName]['Default'] = (string) $args[0];
		}
		if ($method == 'nullable') {
			self::$instances[$this->tableName][$this->fieldName]['Null'] = 'YES';
		}
		return $this;
	}

}