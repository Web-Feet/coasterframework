<?php namespace CoasterCms\Http\Controllers\Frontend;

use CoasterCms\Helpers\View\FormMessage;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;

class InstallController extends Controller
{

    public function getIndex()
    {
        if ($redirect = $this->_checkRedirect('install')) {
            return redirect($redirect)->send();
        }

        View::make('coaster::asset_builder.main')->render();

        $installContent = View::make('coaster::pages.install', ['stage' => 'database']);

        return View::make('coaster::template.main', ['site_name' => '', 'title' => '', 'content' => $installContent, 'modals' => '']);
    }

    public function postIndex()
    {
        if ($redirect = $this->_checkRedirect('install')) {
            return redirect($redirect)->send();
        }

        $details = Request::all();

        $v = Validator::make($details, array('host' => 'required', 'user' => 'required', 'name' => 'required'));
        if (!$v->passes()) {
            FormMessage::set($v->messages());
            return $this->getIndex();
        }

        try {
            $db = new \PDO('mysql:dbname='.$details['name'].';host='.$details['host'], $details['user'], $details['password']);
        } catch (\PDOException $e) {
            switch ($e->getCode()) {
                case 1045: FormMessage::add('user', $e->getMessage()); break;
                case 1049: FormMessage::add('name', $e->getMessage()); break;
                case 2003:
                case 2005:
                    FormMessage::add('host', $e->getMessage());
                    break;
                default: dd($e);
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
        $dotenv = new \Dotenv\Dotenv(base_path()); // Laravel 5.2
        foreach ($dotenv->load() as $env) {
            $envParts = explode('=', $env);
            if (key_exists($envParts[0], $updateEnv)) {
                $envFile = str_replace($env, $envParts[0].'='.$updateEnv[$envParts[0]], $envFile);
            }
        }

        file_put_contents(base_path('.env'), $envFile);

        Storage::put('install.txt', 'add-tables');

        return redirect('install/database')->send();

    }

    public function getDatabase()
    {
        if ($redirect = $this->_checkRedirect('install/database')) {
            return redirect($redirect)->send();
        }

        Artisan::call('migrate', ['--path' => '/vendor/web-feet/coasterframework/database/migrations']);

        Storage::put('install.txt', 'add-user');

        return redirect('install/admin')->send();
    }

    public function getAdmin()
    {
        if ($redirect = $this->_checkRedirect('install/admin')) {
            return redirect($redirect)->send();
        }

        View::make('coaster::asset_builder.main')->render();

        $installContent = View::make('coaster::pages.install', ['stage' => 'adduser']);

        return View::make('coaster::template.main', ['site_name' => '', 'title' => '', 'content' => $installContent, 'modals' => '']);
    }


    public function postAdmin()
    {
        if ($redirect = $this->_checkRedirect('install/admin')) {
            return redirect($redirect)->send();
        }

        $details = Request::all();

        $v = Validator::make($details, array('email' => 'required|email', 'password' => 'required|confirmed|min:4'));
        if (!$v->passes()) {
            FormMessage::set($v->messages());
            return $this->getAdmin();
        }

        $date = new \DateTime;

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

        Storage::put('install.txt', 'complete-welcome');

        View::make('coaster::asset_builder.main')->render();

        $installContent = View::make('coaster::pages.install', ['stage' => 'complete']);

        return View::make('coaster::template.main', ['site_name' => '', 'title' => '', 'content' => $installContent, 'modals' => '']);
    }

    public function missingMethod($parameters = [])
    {
        return redirect($this->_checkRedirect())->send();
    }

    private function _checkRedirect($current = null)
    {
        switch (Storage::get('install.txt')) {
            case 'set-env':
                $redirect = 'install';
                break;
            case 'add-tables':
                $redirect = 'install/database';
                break;
            case 'add-user':
                $redirect = 'install/admin';
                break;
            default:
                $redirect = '';
        }
        if ($current == $redirect) {
            $redirect = '';
        }
        return $redirect;
    }

}