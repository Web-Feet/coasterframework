<?php namespace CoasterCms\Http\Controllers\AdminControllers;

use Auth;
use Cache;
use CoasterCms\Helpers\Admin\Validation\Database;
use CoasterCms\Helpers\Cms\File\Directory;
use CoasterCms\Http\Controllers\AdminController as Controller;
use CoasterCms\Models\AdminLog;
use CoasterCms\Models\Block;
use CoasterCms\Models\Language;
use CoasterCms\Models\Page;
use CoasterCms\Models\PageSearchData;
use CoasterCms\Models\Setting;
use CoasterCms\Models\Theme;
use DateTimeHelper;
use DB;
use Request;
use URL;
use View;

class SystemController extends Controller
{
    protected $dot_replace = ':@dot:';

    public function postIndex()
    {
        if (Auth::action('system.update')) {
            $settings = Request::all();
            unset($settings['_token']);
            foreach ($settings as $setting => $value) {
                $setting = str_replace($this->dot_replace, '.', $setting);
                $current_setting = Setting::where('name', '=', $setting)->where('editable', '=', 1)->first();
                if (!empty($current_setting)) {
                    if ($setting == 'site.secure_folders') {
                        $newValues = explode(',',$value);
                        foreach ($newValues as $k => $folder) {
                            $folder = trim($folder);
                            if (empty($folder)) {
                                unset($newValues[$k]);
                            } else {
                                $newValues[$k] = '/'.trim($folder, '/').'/';
                            }
                        }
                        foreach ($newValues as $k => $folder) {
                            foreach ($newValues as $kCheck => $folderCheck) {
                                if ($k != $kCheck && strpos($folder, $folderCheck) === 0) {
                                    unset($newValues[$k]);
                                }
                            }
                        }
                        $oldValues = explode(',', $current_setting->value);
                        foreach ($oldValues as $k => $folder) {
                            $folder = trim($folder);
                            if (empty($folder)) {
                                unset($oldValues[$k]);
                            } else {
                                $oldValues[$k] = '/'.trim($folder, '/').'/';
                            }
                        }
                        $toSecure = array_diff($newValues, $oldValues);
                        $toUnSecure = array_diff($oldValues, $newValues);
                        foreach ($toSecure as $newSecureFolder) {
                            if (is_dir(public_path().'/uploads'.$newSecureFolder)) {
                                Directory::copy(public_path() . '/uploads' . $newSecureFolder, storage_path() . '/uploads' . $newSecureFolder);
                                Directory::remove(public_path() . '/uploads' . $newSecureFolder, true);
                            } else {
                                @mkdir(storage_path() . '/uploads' . $newSecureFolder, 0777, true);
                            }
                        }
                        foreach ($toUnSecure as $newUnSecureFolder) {
                            if (is_dir(storage_path() . '/uploads' . $newUnSecureFolder)) {
                                Directory::copy(storage_path().'/uploads'.$newUnSecureFolder, public_path().'/uploads'.$newUnSecureFolder);
                                Directory::remove(storage_path().'/uploads'.$newUnSecureFolder);
                            } else {
                                @mkdir(public_path().'/uploads'.$newUnSecureFolder, 0777, true);
                            }
                        }
                        $value = implode(',',$newValues);
                    }
                    $current_setting->value = $value;
                    $current_setting->save();
                    if (in_array($setting, ['frontend.theme', 'admin.default_template'])) {
                        Theme::templateIdUpdate();
                    }
                }
            }
            AdminLog::new_log('System settings updated');
            $this->addAlert('success', 'System Settings Updated');
        }

        return \redirect()->route('coaster.admin.system');
    }

    public function getIndex()
    {
        $database_structure = $this->_db_messages();

        $search_data = PageSearchData::orderBy('updated_at', 'asc')->first();
        if (!empty($search_data)) {
            $last_indexed_search = DateTimeHelper::display($search_data->updated_at);
        } else {
            $last_indexed_search = false;
        }

        $update = Auth::action('system.update');
        if ($update) {
            $settings = Setting::where('hidden', '=', '0')->orWhere('editable', '=', 1)->get();
        } else {
            $settings = Setting::where('hidden', '=', '0')->get();
        }
        foreach ($settings as $k => $setting) {
            if (!$update) {
                $settings[$k]->editable = 0;
            }
            $note = '';
            switch ($setting->name) {
                case 'frontend.language':
                    $custom = new \stdClass;
                    $custom->selected = $setting->value;
                    $custom->options = Language::selectArray();
                    break;
                case 'frontend.theme':
                    $custom = new \stdClass;
                    $custom->selected = $setting->value;
                    $custom->options = Theme::selectArray();
                    break;
                case 'admin.default_template':
                    $custom = new \stdClass;
                    $custom->selected = $setting->value;
                    $custom->options = Theme::get_template_list($setting->value);
                    break;
                case 'frontend.language_fallback':
                case 'frontend.strong_tags':
                case 'admin.advanced_permissions':
                case 'admin.publishing':
                    $custom = new \stdClass;
                    $custom->selected = $setting->value;
                    $custom->options = [1 => 'On', 0 => 'Off'];
                    break;
                case 'admin.title_block':
                    $custom = new \stdClass;
                    $custom->selected = $setting->value;
                    $custom->options = Block::nameToNameArray();
                    break;
                case 'site.pages':
                    $total_pages = Page::get_total();
                    $note = 'set to 0 for no limit - currently ' . $total_pages . ' normal pages in use';
                    break;
                case 'site.groups':
                    $total_pages = Page::get_total(true);
                    $note = 'set to 0 for no limit on group pages - currently ' . $total_pages . ' pages in use';
                    break;
                case 'site.secure_folders':
                    $note = 'list of comma separated folders, will copy files to secure folders and vice versa on update';
                    break;
                default:
                    $custom = null;
            }
            if (empty($custom)) {
                $settings[$k]->value = $setting->value;
            } else {
                $settings[$k]->value = $custom;
            }
            $settings[$k]->note = !empty($note)?$note:null;
            $settings[$k]->name = str_replace('.', $this->dot_replace, $setting->name);
        }

        $upgrade = new \stdClass;
        $upgrade->from = config('coaster::site.version');
        $upgrade->to = Setting::latestTag();
        $upgrade->required = version_compare(config('coaster::site.version'), Setting::latestTag(), '<');

        $this->layoutData['content'] = View::make('coaster::pages.system', array('database_structure' => $database_structure, 'last_indexed_search' => $last_indexed_search, 'site_details' => $settings, 'can_index_search' => Auth::action('system.search'), 'can_validate' => Auth::action('system.validate-db'), 'can_upgrade' => Auth::action('system.upgrade'), 'upgrade' => $upgrade));
    }

    public function getWpLogin()
    {
        $blog_login_url = URL::to('/' . trim(config('coaster::blog.url'), '/') . '/wp-login.php');
        $blog_admin_url = URL::to('/' . trim(config('coaster::blog.url'), '/') . '/wp-admin/');

        $data_to_post = array(
            'rememberme' => 'forever',
            'redirect_to' => $blog_admin_url,
            'testcookie' => 1
        );

        $authUser = Auth::user();
        if (!empty($authUser->blog_login)) {
            $blog_details = unserialize($authUser->blog_login);
            $data_to_post['log'] = $blog_details['login'];
            $data_to_post['pwd'] = $blog_details['password'];
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $blog_login_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_to_post);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_COOKIESESSION, true);
        curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . '/../../cookie.txt');

        $result = curl_exec($ch);
        curl_close($ch);

        foreach (explode("\n", $result) as $line) {
            if (substr(ltrim($line), 0, 1) !== '<') {
                header($line, false);
            } else {
                break;
            }
        }

        header('Location: ' . $blog_admin_url);
        return '';
    }

    public function getSearch()
    {
        PageSearchData::updateAllSearchData();
        AdminLog::new_log('Search index updated');
        // as ajax call
        return 1;
    }

    public function getValidateDb($fix = null)
    {
        $messages = $this->_db_messages($fix);
        if (isset($messages['redirect'])) {
            return $messages['redirect'];
        }
        $this->layoutData['content'] = View::make('coaster::pages.system.validate-db', $messages);
        return null;
    }

    public function getUpgrade($update = null)
    {
        $run = false;
        $error = '';
        $message = '';

        if (empty($update)) {

            $run = true;
            $message = 'Composer is required to run the upgrade and must be executable from the sites root directory.<br />
            The process can take a minute or so to complete.';

        } elseif (version_compare(config('coaster::site.version'), Setting::latestTag(), '<') && Setting::latestTag() != "not-found") {

            if (!getenv('HOME') && !getenv('COMPOSER_HOME')) {
                putenv("COMPOSER_HOME=".exec('pwd')."/.composer");
            }

            $coasterStorage = storage_path(config('coaster::site.storage_path'));
            $logFile = $coasterStorage . '/upgrade.log';
            if (!file_exists($logFile) && is_writable($coasterStorage)) {
                file_put_contents('', $logFile);
            }
            if (is_writable($logFile)) {
                shell_exec('cd ' . base_path() . '; composer -n update 2>' . $logFile . ';');
                $upgradeLog = file_get_contents($logFile);

                if (!empty($upgradeLog) && stripos($upgradeLog, 'Generating autoload files') !== false) {
                    Cache::put('coaster::site.version', Setting::latestTag(), 30);
                    $message = 'Successfully upgraded to version ' . Setting::latestTag();
                } else {
                    $error = 'Upgrade failed, composer might not be installed or there might have been an error: <br /><br />';
                    $error .= '<pre>' . str_replace("\n", "<br />", $upgradeLog) . '</pre>';
                }
            } else {
                $error = 'Can\'t create or write to log file :'.$logFile;
            }

        } else {
            $message = 'Already at the latest version '.config('coaster::site.version');
        }

        $this->layoutData['content'] = View::make('coaster::pages.system.upgrade', ['message' => $message, 'error' => $error, 'run' => $run]);

    }

    public function postKeys($key = null)
    {
        if (strpos($key, 'browser') !== false) {
            return config('coaster::key.' . $key);
        } else {
            return 0;
        }
    }

    private function _db_messages($basic_fix = 0)
    {
        $notices = [];
        $warnings = [];
        $errors = [];

        $json = Database::generateJson();

        if (!empty($json)) {

            // get version tables
            $migrationTables = json_decode($json);

            // get current tables
            $dbTables = new \stdClass;
            $dbTablesSelect = DB::select('Show Tables');
            $prefix = DB::getTablePrefix();
            foreach ($dbTablesSelect as $table) {
                $table = (array) $table;
                $table = reset($table);
                if (empty($prefix) || strrpos($table, $prefix) === 0) {
                    $name = substr($table, strlen($prefix));
                    $dbTables->$name = DB::select('SHOW COLUMNS FROM ' . $table);
                }
            }
            unset($dbTables->migrations);

            // compare
            foreach ($dbTables as $dbTableName => $dbColumns) {
                if (!empty($migrationTables->$dbTableName)) {
                    foreach ($dbColumns as $dbColumnData) {
                        if (!empty($migrationTables->$dbTableName->{$dbColumnData->Field})) {
                            $update = false;
                            $migrationFieldData = $migrationTables->$dbTableName->{$dbColumnData->Field};
                            if ($migrationFieldData->Type !== $dbColumnData->Type) {
                                $update = true;
                                $warnings[] = 'Warning: ' . $dbTableName . '/' . $dbColumnData->Field . ' type needs changing, \'' . $dbColumnData->Type . '\' => \'' . $migrationFieldData->Type . '\'';
                            }
                            if ($migrationFieldData->Null !== $dbColumnData->Null) {
                                $update = true;
                                $warnings[] = 'Warning: ' . $dbTableName . '/' . $dbColumnData->Field . ' allow null setting needs changing, \'' . $dbColumnData->Null . '\' => \'' . $migrationFieldData->Null . '\'';
                            }
                            if ($migrationFieldData->Extra !== $dbColumnData->Extra) {
                                $update = true;
                                if (empty($migrationFieldData->Extra)) {
                                    $warnings[] = 'Warning: ' . $dbTableName . '/' . $dbColumnData->Field . ' \'' . $dbColumnData->Extra . '\' setting needs removing';
                                } else {
                                    $warnings[] = 'Warning: ' . $dbTableName . '/' . $dbColumnData->Field . ' \'' . $migrationFieldData->Extra . '\' needs setting';
                                }
                            }
                            if ($migrationFieldData->Default !== $dbColumnData->Default) {
                                if ($basic_fix == 1 && $migrationFieldData->Default === null) {
                                    DB::statement('ALTER TABLE ' . $prefix . $dbTableName . ' ALTER COLUMN `' . $dbColumnData->Field . '` DROP DEFAULT;');
                                } else {
                                    $update = true;
                                }
                                $warnings[] = 'Warning: ' . $dbTableName . '/' . $dbColumnData->Field . ' default values needs changing, ' . ($dbColumnData->Default!==null?'\''.$dbColumnData->Default.'\'':'No default') . ' => ' .  ($migrationFieldData->Default!==null?'\''.$migrationFieldData->Default.'\'':'No default');
                            }
                            if ($basic_fix == 1 && $update) {
                                if (strpos($migrationFieldData->Default, 'CURRENT_TIMESTAMP') !== false) {
                                    $default = ' DEFAULT ' . $migrationFieldData->Default;
                                } elseif ($migrationFieldData->Default !== null) {
                                    $default = ' DEFAULT \'' . $migrationFieldData->Default . '\'';
                                } else {
                                    $default = '';
                                }
                                if ($migrationFieldData->Null == 'NO') {
                                    $null = ' NOT NULL';
                                } else {
                                    $null = ' NULL';
                                }
                                DB::statement('ALTER TABLE ' . $prefix . $dbTableName . ' MODIFY COLUMN `' . $dbColumnData->Field . '` ' . $migrationFieldData->Type . $null . $default . ' ' . $migrationFieldData->Extra . ';');
                            }
                            unset($migrationTables->$dbTableName->{$dbColumnData->Field});
                        } else {
                            $notices[] = 'Notice: Extra Field ' . $dbTableName . '/' . $dbColumnData->Field . ' found';
                        }
                    }
                    $check_empty = (array) $migrationTables->$dbTableName;
                    if (empty($check_empty)) {
                        unset($migrationTables->$dbTableName);
                    }
                } else {
                    $notices[] = 'Notice: Extra Table ' . $dbTableName . ' found';
                }
            }
            // missing tables and notices/warnings
            $errors = (array) $migrationTables;
        }

        if ($basic_fix == 1) {
            return ['redirect' => \redirect()->route('coaster.admin.system.validate-db')];
        }

        return ['errors' => $errors, 'warnings' => $warnings, 'notices' => $notices];
    }

}
