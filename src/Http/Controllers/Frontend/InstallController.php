<?php namespace CoasterCms\Http\Controllers\Frontend;

use Artisan;
use Carbon\Carbon;
use CoasterCms\Helpers\View\FormMessage;
use CoasterCms\Models\Theme;
use DB;
use Dotenv\Dotenv;
use File;
use Hash;
use Illuminate\Routing\Controller;
use Request;
use Validator;
use View;

class InstallController extends Controller
{

    public function getIndex()
    {
        if ($redirect = $this->_checkRedirect('install')) {
            \redirect($redirect)->send();
        }

        View::make('coaster::asset_builder.main')->render();

        $installContent = View::make('coaster::pages.install', ['stage' => 'database']);

        return View::make('coaster::template.main', ['site_name' => 'Coaster CMS', 'title' => 'Install Database', 'content' => $installContent, 'modals' => '', 'system_menu_icons' => []]);
    }

    public function postIndex()
    {
        if ($redirect = $this->_checkRedirect('install')) {
            \redirect($redirect)->send();
        }

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
        $dotenv = new Dotenv(base_path()); // Laravel 5.2
        foreach ($dotenv->load() as $env) {
            $envParts = explode('=', $env);
            if (key_exists($envParts[0], $updateEnv)) {
                $envFile = str_replace($env, $envParts[0].'='.$updateEnv[$envParts[0]], $envFile);
            }
        }

        file_put_contents(base_path('.env'), $envFile);

        $storagePath = storage_path(config('coaster::site.storage_path')) . '/';
        File::put($storagePath.'install.txt', 'add-tables');

        \redirect('install/database')->send();

    }

    public function getDatabase()
    {
        if ($redirect = $this->_checkRedirect('install/database')) {
            \redirect($redirect)->send();
        }

        Artisan::call('migrate', ['--path' => '/vendor/web-feet/coasterframework/database/migrations']);

        $storagePath = storage_path(config('coaster::site.storage_path')) . '/';
        File::put($storagePath.'install.txt', 'add-user');

        \redirect('install/admin')->send();
    }

    public function getAdmin()
    {
        if ($redirect = $this->_checkRedirect('install/admin')) {
            \redirect($redirect)->send();
        }

        View::make('coaster::asset_builder.main')->render();

        $installContent = View::make('coaster::pages.install', ['stage' => 'adduser']);

        return View::make('coaster::template.main', ['site_name' => 'Coaster CMS', 'title' => 'Install User', 'content' => $installContent, 'modals' => '', 'system_menu_icons' => []]);
    }


    public function postAdmin()
    {
        if ($redirect = $this->_checkRedirect('install/admin')) {
            \redirect($redirect)->send();
        }

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

        $storagePath = storage_path(config('coaster::site.storage_path')) . '/';
        File::put($storagePath.'install.txt', 'theme');

        \redirect('install/theme')->send();
    }

    public function getTheme()
    {
        if ($redirect = $this->_checkRedirect('install/theme')) {
            \redirect($redirect)->send();
        }

        View::make('coaster::asset_builder.main')->render();

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

        $installContent = View::make('coaster::pages.install', ['stage' => 'theme', 'themes' => $themes, 'defaultTheme' => 'coaster2016.zip']);

        return View::make('coaster::template.main', ['site_name' => 'Coaster CMS', 'title' => 'Install User', 'content' => $installContent, 'modals' => '', 'system_menu_icons' => []]);
    }


    public function postTheme()
    {
        if ($redirect = $this->_checkRedirect('install/theme')) {
            \redirect($redirect)->send();
        }

        $details = Request::all();

        if (!empty($details['theme'])) {
            $error = Theme::unzip($details['theme']);
            if (!$error) {
                $withPageData = !empty($details['page-data'])?1:0;
                Theme::install(substr($details['theme'], 0, -4), ['withPageData' => $withPageData]);
            } else {
                FormMessage::add('theme', $error);
                return $this->getTheme();
            }
        }

        $storagePath = storage_path(config('coaster::site.storage_path')) . '/';
        File::put($storagePath.'install.txt', 'complete-welcome');

        View::make('coaster::asset_builder.main')->render();

        $installContent = View::make('coaster::pages.install', ['stage' => 'complete']);

        return View::make('coaster::template.main', ['site_name' => 'Coaster CMS', 'title' => 'Install Complete', 'content' => $installContent, 'modals' => '', 'system_menu_icons' => []]);
    }

    public function missingMethod($parameters = [])
    {
        \redirect($this->_checkRedirect())->send();
    }

    private function _checkRedirect($current = null)
    {
        $storagePath = storage_path(config('coaster::site.storage_path')) . '/';
        if (!is_dir($storagePath)) {
            print "Run: php -f ". base_path('vendor/web-feet/coasterframework/updateAssets')."<br />";
            print "Then refresh the page to enter the install";
            exit;
        }
        if (!File::exists($storagePath.'install.txt')) {
            File::put($storagePath.'install.txt', 'set-env');
        }
        switch (File::get($storagePath.'install.txt')) {
            case 'set-env':
                $redirect = 'install';
                break;
            case 'add-tables':
                $redirect = 'install/database';
                break;
            case 'add-user':
                $redirect = 'install/admin';
                break;
            case 'theme':
                $redirect = 'install/theme';
                break;
            default:
                $redirect = 'install';
        }
        if ($current == $redirect) {
            $redirect = '';
        }
        return $redirect;
    }

}