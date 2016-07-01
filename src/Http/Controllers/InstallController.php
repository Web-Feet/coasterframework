<?php namespace CoasterCms\Http\Controllers;

use Artisan;
use Carbon\Carbon;
use CoasterCms\Helpers\Core\Page\Install;
use CoasterCms\Helpers\Core\View\FormMessage;
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
        ];
        
        $currentRouteName = Request::route()->getName();
        $installRoute = Install::getRedirectRoute();

        if (strpos($currentRouteName, $installRoute) !== 0) {
            \redirect()->route($installRoute)->send();
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
    

    public function setupDatabase()
    {
        $this->layoutData['title'] = 'Install Database';
        $this->layoutData['content'] = View::make('coaster::pages.install', ['stage' => 'database']);
    }

    public function saveDatabaseSettings()
    {
        $details = Request::all();

        $v = Validator::make($details, array('host' => 'required', 'user' => 'required', 'name' => 'required'));
        if (!$v->passes()) {
            FormMessage::set($v->messages());
            return $this->getIndex();
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
            return $this->getIndex();
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
            return $this->getAdmin();
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
        $themes = ['' => '-- None --'];

        $themesPath = base_path('resources/views/themes');
        if (is_dir($themesPath)) {
            foreach (scandir($themesPath) as $themeFile) {
                if (!is_dir($themesPath . '/' . $themeFile) && substr($themeFile, -4) == '.zip') {
                    $themeName = substr($themeFile, 0, -4);
                    $themes[$themeFile] = $themeName;
                    if ($themeName == 'default') {
                        $themes[$themeFile] .= ' (minimal theme)';
                    }
                }
            }
        }

        $this->layoutData['title'] = 'Install Theme';
        $this->layoutData['content'] = View::make('coaster::pages.install', ['stage' => 'theme', 'themes' => $themes, 'defaultTheme' => 'coaster2016.zip']);
    }

    public function installTheme()
    {
        $details = Request::all();

        $error = false;
        if (!empty($details['theme'])) {
            if (!($error = Theme::unzip($details['theme']))) {
                $withPageData = !empty($details['page-data'])?1:0;
                $result = Theme::install(substr($details['theme'], 0, -4), ['withPageData' => $withPageData]);
                $error = $result['error']?$result['response']:$error;
            }
        }

        if ($error) {
            FormMessage::add('theme', $error);
            $this->setupTheme();
        } else {
            Install::setInstallState('complete-welcome');
            $this->layoutData['title'] = 'Install Complete';
            $this->layoutData['content'] = View::make('coaster::pages.install', ['stage' => 'complete']);
        }

    }

}