<?php namespace CoasterCms\Helpers\Admin\Validation;

class Database {

    public static $test = [];

	public static function generateJson($migrationsFolder = null)
	{
        if (empty($migrationsFolder)) {
            $migrationsFolder = realpath(__DIR__ . '/../../../../database/migrations');
        }

        if (!is_dir($migrationsFolder)) {
            return [];
        }
        
		foreach (scandir($migrationsFolder) as $file) {
			if (in_array($file, ['.', '..'])) continue;

            $migration = file_get_contents($migrationsFolder . '/' . $file);

            $migration = str_replace(
                [
                    'use Illuminate\Database\Migrations\Migration;',
                    'use Illuminate\Database\Schema\Blueprint;',
                    'use Illuminate\Support\Facades\Schema;',
                    'use Illuminate\Support\Facades\DB;'
                ],
                '',
                $migration
            );

            $migration = str_replace(
                '<?php',
                '
                use Illuminate\Database\Migrations\Migration;
                use CoasterCms\Helpers\Admin\Validation\Database\Blueprint;
                use CoasterCms\Helpers\Admin\Validation\Database\Schema;
                use CoasterCms\Helpers\Admin\Validation\Database\DB;',
                $migration
            );

            $fileClass = '\\';
            $fileClassWords = array_slice(explode('_', substr($file, 0, -4)), 4);
            foreach ($fileClassWords as $fileClassWord) {
                $fileClass .= ucwords($fileClassWord);
            }
            eval($migration);
            (new $fileClass)->up();

		}

        foreach (Database\Column::$instances as $table => $tableDetails) {
            foreach ($tableDetails as $column => $columnDetails) {
                if ($columnDetails['Type'] == 'timestamp' && !$columnDetails['Default'] && $columnDetails['Null'] == 'NO') {
                    Database\Column::$instances[$table][$column]['Null'] = 'YES';
                }
            }
        }

        ksort(Database\Column::$instances);
        return json_encode(Database\Column::$instances);
	}
}
