<?php namespace CoasterCms\Http\Controllers;

use Artisan;
use Carbon\Carbon;
use CoasterCms\Helpers\Admin\Routes;
use CoasterCms\Helpers\Cms\File\File;
use CoasterCms\Helpers\Cms\Install;
use CoasterCms\Facades\FormMessage;
use CoasterCms\Models\Setting;
use CoasterCms\Models\Theme;
use CoasterCms\Models\User;
use DB;
use Dotenv\Dotenv;
use Dotenv\Loader;
use Hash;
use Illuminate\Routing\Controller;
use Request;
use Validator;
use View;

class InstallController extends Controller
{

    protected $layout;
    protected $layoutData;

    public function __construct()
    {
        View::make('coaster::asset_builder.main')->render();

        $this->layout = 'coaster::template.main';
        $this->layoutData = [
            'site_name' => config('coaster::site.name'),
            'title' => '',
            'system_menu' => '',
            'sections_menu' => '',
            'modals' => '',
            'content' => '',
            'coaster_routes' => Routes::jsonRoutes()
        ];

        $currentRouteName = Request::route()->getName();
        $installRoute = Install::getRedirectRoute();

        if (strpos($currentRouteName, $installRoute) !== 0) {
            \redirect()->route($installRoute)->send();
            exit;
        }
    }

    public function callAction($method, $parameters)
    {
        $actionResponse = call_user_func_array([$this, $method], $parameters);

        if (is_null($actionResponse)) {
            return View::make($this->layout, $this->layoutData);
        } else {
            return $actionResponse;
        }
    }

    public function checkPermissions($next = false)
    {
        $dirs = [
            storage_path('app/coaster') => true,
            public_path('coaster') => true,
            public_path('themes') => true,
            public_path('uploads') => true,
            base_path('.env') => false,
        ];
        $continue = true;

        foreach ($dirs as $dir => &$isWritable) {
            try {
                if (!file_exists($dir)) {
                    if (!$isWritable) { // is dir at this point
                        file_put_contents($dir, '');
                    } else {
                        \mkdir($dir, 0777, true);
                    }
                }
            } catch (\Exception $e) {}
            if (!$isWritable = is_writable($dir)) {
                $continue = false;
            }
        }

        if ($next && $continue) {
            Install::setInstallState('coaster.install.database');
            return \redirect()->route('coaster.install.database');
        }

        $this->layoutData['title'] = 'Check Permissions';
        $this->layoutData['content'] = View::make('coaster::pages.install', ['stage' => 'permissions', 'dirs' => $dirs, 'continue' => $continue]);
        return null;
    }

    public function setupDatabase()
    {
        // basic db prefix checks
        $envFileContents = File::getEnvContents();
        $dbConf = file_get_contents(config_path('database.php'));
        $allowPrefix = (strpos($envFileContents, 'DB_PREFIX') !== false) && (strpos($dbConf, "'prefix' => env('DB_PREFIX', '')") !== false);

        $this->layoutData['title'] = 'Install Database';
        $this->layoutData['content'] = View::make('coaster::pages.install', ['stage' => 'database', 'allowPrefix' => $allowPrefix]);
    }

    public function saveDatabaseSettings()
    {
        $details = Request::all();

        $v = Validator::make($details, ['user' => 'required', 'name' => 'required']);
        if (!$v->passes()) {
            FormMessage::set($v->messages());
            return $this->setupDatabase();
        }

        $hostPort = explode(':', $details['host']);
        if (count($hostPort) === 2 && (int) $hostPort[1]) {
            list($details['host'], $port) = $hostPort;
        }

        try {
            $host = ($details['host'] ?: 'localhost') . (isset($port) ? ';port='.$port : '');
            if (stristr($details['name'], '.sqlite') !== false) {
                $details['connection'] = 'sqlite';
                with(new \Illuminate\Database\Connectors\SQLiteConnector)->connect(['database' => $details['name']]);
            }
            else
            {
                $details['connection'] = 'mysql';
                new \PDO('mysql:dbname='.$details['name'].';host='.$host, $details['user'], $details['password']);
            }
        } catch (\PDOException $e) {
            switch ($e->getCode()) {
                case 1045: FormMessage::add('user', $e->getMessage()); break;
                case 1049: FormMessage::add('name', $e->getMessage()); break;
                case 2003:
                case 2005:
                    FormMessage::add('host', $e->getMessage());
                    break;
                default:
                    FormMessage::add('host', $e->getMessage());
            }
            return $this->setupDatabase();
        }

        $updateEnv = [
            'DB_CONNECTION' => $details['connection'],
            'DB_HOST' => $details['host'],
            'DB_DATABASE' => $details['name'],
            'DB_PREFIX' => !empty($details['prefix']) ? $details['prefix'] : '',
            'DB_USERNAME' => $details['user'],
            'DB_PASSWORD' => $details['password']
        ];

        if (isset($port)) {
            $updateEnv['DB_PORT'] = $port;
        }

        foreach ($updateEnv as &$value) {
            if (strpos($value, ' ') !== false) {
                $value = '"' . $value . '"';
            }
        }

        try {
            $envFileContents = File::getEnvContents();
            $dotEnv = Dotenv::create([base_path()]);
            foreach ($dotEnv->load() as $envVar => $envValue) {
                if (key_exists($envVar, $updateEnv)) {
                    $envFileContents = preg_replace('#' . $envVar . '.*=.*' . $envValue . '.*#', $envVar . '=' . $updateEnv[$envVar], $envFileContents);
                }
            }

            file_put_contents(base_path('.env'), $envFileContents);
        } catch (\Exception $e) {
            FormMessage::add('host', 'can\'t write settings to the .env file, check it is writable for the installation');
            return $this->setupDatabase();
        }

        Install::setInstallState('coaster.install.databaseMigrate');

        return \redirect()->route('coaster.install.databaseMigrate');
    }

    public function runDatabaseMigrations($skipEnvCheck = false)
    {
        $unMatchedEnvVars = [];
        $envFile = Dotenv::create([base_path()]);
        foreach ($envFile->load() as $envVar => $envValue) {
            $envValue = trim($envValue, '"');
            if (getenv($envVar) !== false && getenv($envVar) != $envValue) {
                $unMatchedEnvVars[$envVar] = $envValue;
            }
        }

        if (!$skipEnvCheck && $unMatchedEnvVars) {

            $this->layoutData['content'] = View::make('coaster::pages.install', ['stage' => 'envCheck', 'unMatchedEnvVars' => $unMatchedEnvVars]);

        } else {
            Artisan::call('migrate', ['--path' => '/vendor/web-feet/coasterframework/database/migrations']);

            Install::setInstallState('coaster.install.admin');

            return \redirect()->route('coaster.install.admin');
        }
    }

    public function setupAdminUser()
    {
        $this->layoutData['title'] = 'Install User';
        $this->layoutData['content'] = View::make('coaster::pages.install', ['stage' => 'adduser', 'currentUsers' => User::count()]);
    }

    public function saveAdminUser()
    {
        $details = Request::all() + ['skip' => ''];

        if (User::count() == 0 || $details['skip'] != 'Skip') {

            $v = Validator::make($details, array('email' => 'required|email', 'password' => 'required|confirmed|min:4'));
            if (!$v->passes()) {
                FormMessage::set($v->messages());
                return $this->setupAdminUser();
            }

            $date = new Carbon;

            DB::table('users')->insert(
                array(
                    array(
                        'active' => 1,
                        'password' => Hash::make($details['password']),
                        'email' => $details['email'],
                        'role_id' => '1',
                        'created_at' => $date,
                        'updated_at' => $date
                    )
                )
            );

        }

        Install::setInstallState('coaster.install.theme');

        return \redirect()->route('coaster.install.theme');
    }

    public function setupTheme()
    {
        $themes = ['' => '-- None/Skip --'];

        $themesPath = base_path('resources/views/themes');
        if (is_dir($themesPath)) {
            foreach (scandir($themesPath) as $themeFile) {
                if (substr($themeFile, -4) == '.zip' || (is_dir($themesPath . '/' . $themeFile) && substr($themeFile, 0, 1) != '.')) {
                    $themeName = (substr($themeFile, -4) == '.zip') ? substr($themeFile, 0, -4) : $themeFile;
                    $themes[$themeName] = $themeName;
                    if ($themeName == 'default') {
                        $themes[$themeName] .= ' (minimal theme)';
                    }
                }
            }
        }

        $this->layoutData['title'] = 'Install Theme';
        $this->layoutData['content'] = View::make('coaster::pages.install', ['stage' => 'theme', 'themes' => $themes, 'defaultTheme' => 'coaster2017']);
    }

    public function installTheme()
    {
        $themeName = Request::input('theme');
        $withPageData = Request::input('page-data');

        $error = false;
        if ($themeName) {
            $installResponse = Theme::install($themeName, ['withPageData' => $withPageData]);
            if ($installResponse->getStatusCode() != 200) {
                FormMessage::add('theme', implode('<br /><br />', $installResponse->getData()));
                $error = true;
            }
            if (($usedThemeSetting = Setting::where('name', '=', 'frontend.theme')->first()) && ($theme = Theme::where('theme', '=', $themeName)->first())) {
                $usedThemeSetting->value = $theme->id;
                $usedThemeSetting->save();
            }

        }

        if (!Request::exists('theme') || $error) {
            $this->setupTheme();
        } else {
            Install::setInstallState('complete-welcome');
            $this->layoutData['title'] = 'Install Complete';
            $this->layoutData['content'] = View::make('coaster::pages.install', ['stage' => 'complete']);
        }
    }

}
