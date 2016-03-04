<?php namespace CoasterCms\Http\Controllers\Backend;

use CoasterCms\Models\AdminLog;
use CoasterCms\Models\Block;
use CoasterCms\Models\Language;
use CoasterCms\Models\Page;
use CoasterCms\Models\PageBlockRepeaterData;
use CoasterCms\Models\PageBlockRepeaterRows;
use CoasterCms\Models\PageSearchData;
use CoasterCms\Models\PageVersion;
use CoasterCms\Models\Setting;
use CoasterCms\Models\Template;
use CoasterCms\Models\Theme;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;

class SystemController extends _Base
{
    protected $dot_replace = ':@dot:';

    public function post_index()
    {
        if (Auth::action('system.update')) {
            $settings = Input::all();
            unset($settings['_token']);
            foreach ($settings as $setting => $value) {
                $setting = str_replace($this->dot_replace, '.', $setting);
                $current_setting = Setting::where('name', '=', $setting)->where('editable', '=', 1)->first();
                if (!empty($current_setting)) {
                    $current_setting->value = $value;
                    $current_setting->save();
                }
            }
            AdminLog::new_log('System settings updated');
            $alert = new \stdClass;
            $alert->type = 'success';
            $alert->header = 'System Settings Updated';
            $alert->content = '';
            $this->layout->alert = $alert;
        }

        return $this->get_index();
    }

    public function get_index()
    {
        $database_structure = $this->_db_messages();

        $search_data = PageSearchData::orderBy('updated_at', 'asc')->first();
        if (!empty($search_data)) {
            $last_indexed_search = strtotime($search_data->updated_at);
        } else {
            $last_indexed_search = 0;
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
                    $custom->options = Theme::get_template_list();
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
                    $true_total_pages = Page::get_total(true);
                    $note = 'set to 0 for no limit (' . $total_pages . ' normal pages in use, total including group pages is ' . $true_total_pages . ')';
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

        $this->layout->content = View::make('coaster::pages.system', array('database_structure' => $database_structure, 'last_indexed_search' => $last_indexed_search, 'site_details' => $settings, 'can_index_search' => Auth::action('system.search'), 'can_validate' => Auth::action('system.validate-db')));
    }

    public function get_wp_login()
    {
        $blog_login_url = Url::to('/' . trim(config('coaster::blog.url'), '/') . '/wp-login.php');
        $blog_admin_url = Url::to('/' . trim(config('coaster::blog.url'), '/') . '/wp-admin/');

        $data_to_post = array(
            'rememberme' => 'forever',
            'redirect_to' => $blog_admin_url,
            'testcookie' => 1
        );

        if (!empty($this->user->blog_login)) {
            $blog_details = unserialize($this->user->blog_login);
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

    public function get_search()
    {
        PageSearchData::update_search_data();
        AdminLog::new_log('Search index updated');
        // as ajax call
        return 1;
    }

    public function get_preview_update()
    {
        $versions = PageVersion::all();
        foreach ($versions as $version) {
            $version->preview_key = base_convert((rand(10, 99) . microtime(true) * 10000), 10, 36);
            $version->save(['system' => true]);
        }
    }

    public function get_repeater_update()
    {
        // 4.4 => 4.5
        $old_format = DB::table('page_blocks_repeater')->where('repeater_id', '>', 0)->orderBy('repeater_id')->get();
        $row_keys = [];
        foreach ($old_format as $old_format_row) {
            if (!isset($row_keys[$old_format_row->repeater_id])) {
                $row_keys[$old_format_row->repeater_id] = [];
            }
            if (empty($row_keys[$old_format_row->repeater_id][$old_format_row->row_id])) {
                $row = new PageBlockRepeaterRows;
                $row->repeater_id = $old_format_row->repeater_id;
                $row->row_id = $old_format_row->row_id;
                $row->save();
                $row_keys[$old_format_row->repeater_id][$old_format_row->row_id] = $row->id;
                $data = new PageBlockRepeaterData;
                $data->row_key = $row_keys[$old_format_row->repeater_id][$old_format_row->row_id];
                $data->block_id = 0;
                $data->content = $old_format_row->row_id; // order
                $data->version = 1;
                $data->save();
            }
            $data = new PageBlockRepeaterData;
            $data->row_key = $row_keys[$old_format_row->repeater_id][$old_format_row->row_id];
            $data->block_id = $old_format_row->block_id;
            $data->content = $old_format_row->content;
            $data->version = 1;
            $data->save();
        }
        DB::table('page_lang')->update(array('live_version' => 1));
        DB::table('page_blocks')->update(array('version' => 1));
        DB::table('page_blocks_default')->update(array('version' => 1));
        $pages = Page::all();
        foreach ($pages as $page) {
            $page_version = new PageVersion;
            $page_version->page_id = $page->id;
            $page_version->version_id = 1;
            $page_version->template = $page->template;
            $page_version->user_id = 1;
            $page_version->created_at = $page->created_at;
            $page_version->save();
        }
    }

    public function get_validate_db($fix = null)
    {
        $messages = $this->_db_messages($fix);
        $this->layout->content = View::make('coaster::pages.system.validate-db', $messages);
    }

    public function post_keys($key = null)
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

        $version = config('coaster::site.version');
        $json = file_get_contents('http://cms.web-feet.co.uk/validate/database/' . $version);
        if (!empty($json)) {

            // get version tables
            $version_tables = json_decode($json);

            // get current tables
            $tables = new \stdClass;
            $db_tables = DB::select('Show Tables');
            $prefix = DB::getTablePrefix();
            foreach ($db_tables as $table) {
                $table = reset($table);
                if (strrpos($table, $prefix) === 0) {
                    $name = substr($table, strlen($prefix));
                    $tables->$name = DB::select('SHOW COLUMNS FROM ' . $table);
                }
            }
            unset($tables->migrations);

            // compare
            $types = array(
                'increments' => 'int(10) unsigned',
                'integer' => 'int(11)',
                'string' => 'varchar(255)',
                'text' => 'text',
                'mediumText' => 'mediumtext',
                'date' => 'date',
                'remember_token' => 'varchar(100)',
                'timestamp' => 'timestamp',
                'timestamps' => 'timestamp'
            );
            foreach ($tables as $name => $columns) {
                if (!empty($version_tables->$name)) {
                    foreach ($columns as $column_data) {
                        if (!empty($version_tables->$name->{$column_data->Field})) {
                            $update = false;
                            $sql = '';
                            $version_field_data = $version_tables->$name->{$column_data->Field};
                            if ((strpos($column_data->Type, 'enum') === 0 && $version_field_data->Type !== $column_data->Type) || (strpos($column_data->Type, 'enum') !== 0 && $types[$version_field_data->Type] !== $column_data->Type)) {
                                $update = true;
                                if (strpos($column_data->Type, 'enum') === 0) {
                                    $types[$version_field_data->Type] = $version_field_data->Type;
                                }
                                $warnings[] = 'Warning: ' . $name . '/' . $column_data->Field . ' type needs changing ' . $column_data->Type . ' => ' . $types[$version_field_data->Type];
                            }
                            if ($version_field_data->Null !== $column_data->Null) {
                                $update = true;
                                $warnings[] = 'Warning: ' . $name . '/' . $column_data->Field . ' allow null setting needs changing ' . $column_data->Null . ' => ' . $version_field_data->Null;
                            }
                            if ($version_field_data->Type == 'increments' && $column_data->Extra != 'auto_increment') {
                                $sql .= ' AUTO_INCREMENT';
                                $warnings[] = 'Warning: ' . $name . '/' . $column_data->Field . ' auto_increment needs setting';
                            }
                            if ($version_field_data->Default !== $column_data->Default && $version_field_data->Default !== strtoupper($column_data->Default . ' ' . $column_data->Extra)) {
                                if (empty($version_field_data->Default) && $version_field_data->Default !== '0' && $basic_fix == 1) {
                                    DB::statement('ALTER TABLE ' . $prefix . $name . ' ALTER COLUMN `' . $column_data->Field . '` DROP DEFAULT;');
                                } else {
                                    if (strpos($version_field_data->Default, 'CURRENT_TIMESTAMP') !== false) {
                                        $sql .= ' DEFAULT ' . $version_field_data->Default;
                                    } else {
                                        $sql .= ' DEFAULT \'' . $version_field_data->Default . '\'';
                                    }
                                }
                                $warnings[] = 'Warning: ' . $name . '/' . $column_data->Field . ' default values needs changing ' . $column_data->Default . ' => ' . $version_field_data->Default;
                            }
                            if ($basic_fix == 1 && ($update || !empty($sql))) {
                                $null = ' NULL';
                                if ($version_field_data->Null == 'NO') {
                                    $null = ' NOT' . $null;
                                }
                                DB::statement('ALTER TABLE ' . $prefix . $name . ' MODIFY COLUMN `' . $column_data->Field . '` ' . $types[$version_field_data->Type] . $null . $sql . ';');
                            }
                            unset($version_tables->$name->{$column_data->Field});
                        } else {
                            $notices[] = 'Notice: Extra Field ' . $name . '/' . $column_data->Field . ' found';
                        }
                    }
                    $check_empty = (array)$version_tables->$name;
                    if (empty($check_empty)) {
                        unset($version_tables->$name);
                    }
                } else {
                    $notices[] = 'Notice: Extra Table ' . $name . ' found';
                }
            }
            // missing tables and notices/warnings
            $errors = (array)$version_tables;
        }

        if ($basic_fix == 1) {
            $warnings = [];
        }

        return ['errors' => $errors, 'warnings' => $warnings, 'notices' => $notices];
    }

}