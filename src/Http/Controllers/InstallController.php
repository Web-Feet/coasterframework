<?php namespace CoasterCms\Http\Controllers;

use Artisan;
use Carbon\Carbon;
use CoasterCms\Helpers\Admin\Routes;
use CoasterCms\Helpers\Cms\Install;
use CoasterCms\Libraries\Builder\FormMessage;
use CoasterCms\Models\Setting;
use CoasterCms\Models\Theme;
use DB;
use Dotenv\Dotenv;
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
            storage_path('app/coaster'),
            public_path('coaster'),
            public_path('themes'),
            public_path('uploads'),
            base_path('.env'),
        ];
        $continue = true;

        $dirs = array_flip($dirs);
        foreach ($dirs as $dir => &$isWritable) {
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
        $envFile = file_get_contents(base_path('.env'));
        $dbConf = file_get_contents(config_path('database.php'));
        $allowPrefix = (strpos($envFile, 'DB_PREFIX') !== false) && (strpos($dbConf, "'prefix' => env('DB_PREFIX', '')") !== false);

        $this->layoutData['title'] = 'Install Database';
        $this->layoutData['content'] = View::make('coaster::pages.install', ['stage' => 'database', 'allowPrefix' => $allowPrefix]);
    }

    public function saveDatabaseSettings()
    {
        $details = Request::all();

        $v = Validator::make($details, array('host' => 'required', 'user' => 'required', 'name' => 'required'));
        if (!$v->passes()) {
            FormMessage::set($v->messages());
            return $this->setupDatabase();
        }

        try {
            new \PDO('mysql:dbname='.$details['name'].';host='.$details['host'], $details['user'], $details['password']);
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

        Artisan::call('key:generate');

        $updateEnv = [
            'DB_HOST' => $details['host'],
            'DB_DATABASE' => $details['name'],
            'DB_PREFIX' => $details['prefix'],
            'DB_USERNAME' => $details['user'],
            'DB_PASSWORD' => $details['password']
        ];

        $envFile = file_get_contents(base_path('.env'));
        $dotEnv = new Dotenv(base_path()); // Laravel 5.2
        foreach ($dotEnv->load() as $env) {
            $envParts = explode('=', $env);
            if (key_exists($envParts[0], $updateEnv)) {
                $envFile = str_replace($env, $envParts[0].'='.$updateEnv[$envParts[0]], $envFile);
            }
        }

        file_put_contents(base_path('.env'), $envFile);

        Install::setInstallState('coaster.install.databaseMigrate');

        return \redirect()->route('coaster.install.databaseMigrate');
    }

    public function runDatabaseMigrations()
    {
        Artisan::call('migrate', ['--path' => '/vendor/web-feet/coasterframework/database/migrations']);

        Install::setInstallState('coaster.install.admin');

        return \redirect()->route('coaster.install.admin');
    }

    public function setupAdminUser()
    {
        $this->layoutData['title'] = 'Install User';
        $this->layoutData['content'] = View::make('coaster::pages.install', ['stage' => 'adduser']);
    }


    public function saveAdminUser()
    {
        $details = Request::all();

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
        $this->layoutData['content'] = View::make('coaster::pages.install', ['stage' => 'theme', 'themes' => $themes, 'defaultTheme' => 'coaster2016']);
    }

    public function installTheme()
    {
        $details = Request::all();

        $error = false;
        if (!empty($details['theme'])) {
            if (!($error = Theme::unzip($details['theme'].'.zip', false))) {
                $withPageData = !empty($details['page-data']) ? 1 : 0;
                $result = Theme::install($details['theme'], ['withPageData' => $withPageData]);
                if ($result['error']) {
                    $error = $result['response'];
                }
                if (($usedThemeSetting = Setting::where('name', '=', 'frontend.theme')->first()) && ($theme = Theme::where('theme', '=', $details['theme'])->first())) {
                    $usedThemeSetting->value = $theme->id;
                    $usedThemeSetting->save();
                }
            }
        }

        if ($error) {
            FormMessage::add('theme', $error);
            $this->setupTheme();
        } else {
            include __DIR__ . '/../../Http/routes/admin.php';
            Install::setInstallState('complete-welcome');
            $this->layoutData['title'] = 'Install Complete';
            $this->layoutData['content'] = View::make('coaster::pages.install', ['stage' => 'complete']);
        }

    }

}