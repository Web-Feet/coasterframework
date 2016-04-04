<?php namespace CoasterCms\Helpers;

use CoasterCms\Helpers\View\CmsBlockInput;
use CoasterCms\Helpers\View\PaginatorRender;
use CoasterCms\Libraries\Blocks;
use CoasterCms\Models\Block;
use CoasterCms\Models\BlockBeacon;
use CoasterCms\Models\BlockCategory;
use CoasterCms\Models\Language;
use CoasterCms\Models\PageLang;
use CoasterCms\Models\PagePublishRequests;
use CoasterCms\Models\PageSearchData;
use CoasterCms\Models\PageVersion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\View;

class BlockManager
{

    public static $to_version = 0;
    public static $current_version = 0;
    public static $publish = false;

    private static $_blockClasses;

    private static function get_model($page_id, $repeater_info = null)
    {
        // model depends on global or page content
        if (!empty($repeater_info->row_id))
            return '\CoasterCms\Models\PageBlockRepeaterData';
        else if (empty($page_id))
            return '\CoasterCms\Models\PageBlockDefault';
        else
            return '\CoasterCms\Models\PageBlock';
    }

    // get individual block data (latest version default)
    public static function get_block($block_id, $page_id = 0, $repeater_info = null, $version = 0)
    {
        $model = self::get_model($page_id, $repeater_info);
        return $model::get_block($block_id, $page_id, $repeater_info, $version);
    }

    // all block updates go through here
    public static function update_block($block_id, $content, $page_id = 0, $repeater_info = null, $publish = 0)
    {
        // repeater stuff if else
        $model = self::get_model($page_id, $repeater_info);
        $model::update_block($block_id, $content, $page_id, $repeater_info);
        if (($publish || self::$publish) && $model == '\CoasterCms\Models\PageBlock') {
            $version = !empty(self::$to_version) ? self::$to_version : PageVersion::latest_version($page_id);
            PageSearchData::update_text($block_id, $content, $page_id, Language::current(), $version);
        }
        // can force page publish on individual block updates
        // 0 = do not publish
        // 1 = always publish
        // 2 = do not publish (unless publishing is off)
        if (($publish == 1) || ($publish == 2 && !config('coaster::admin.publishing'))) {
            $latestVersion = PageVersion::latest_version($page_id, true);
            if (!empty($latestVersion)) {
                $latestVersion->publish();
            }
        }
    }

    public static function tab_contents($blocks, $block_contents, $item, $button, $page_details = null, $tab_index = 1)
    {
        $tab_headers = array();
        $tab_contents = array();

        if (!empty($page_details)) {
            $tab_headers[0] = 'Page Info';
            $tab_contents[0] = '';
            if (empty($page_details->id)) {

                if (!empty($page_details->in_group)) {
                    $tab_contents[0] .= \Form::hidden('page_info[parent]', $page_details->pages->selected);
                    $page_details->link = 0;
                } else {
                    $tab_contents[0] .=
                        CmsBlockInput::make('select', array('name' => 'page_info[type]', 'label' => 'Page Type', 'content' => $page_details->types)) .
                        CmsBlockInput::make('select', array('name' => 'page_info[parent]', 'label' => 'Parent Page', 'content' => $page_details->pages));
                }

            } elseif (Auth::action('themes.beacons-update')) {
                $dropdownOptions = BlockBeacon::getDropdownOptions($page_details->id);
                if (!empty($dropdownOptions->options)) {
                    $tab_contents[0] .=
                        CmsBlockInput::make('selectmultiple', array('name' => 'page_info[beacons]', 'label' => 'Page Beacons', 'content' => $dropdownOptions));
                }
            }

            $fullUrls = [0 => '/'];
            foreach (PageLang::getPreloadedFullPaths() as $pageId => $details) {
                $fullUrls[$pageId] = rtrim($details->full_url, '/') . '/';
            }

            $isEnabled = (config('coaster::admin.publishing') > 0 && Auth::action('pages.version-publish', ['page_id' => $page_details->id])) || config('coaster::admin.publishing') == 0;
            $page_details->disabled = !($isEnabled || empty($page_details->id));
            $tab_contents[0] .= View::make('coaster::partials.tabs.page_info.page_info', array('page_details' => $page_details, 'urlArray' => $fullUrls))->render();

            if (!empty($page_details->menus) && Auth::action('menus')) {
                $tab_contents[0] .= View::make('coaster::partials.tabs.page_info.menu_info', array('page_details' => $page_details))->render();
            }

            $page_details->disabled = !$isEnabled;
            $tab_contents[0] .= View::make('coaster::partials.tabs.page_info.live_options', array('page_details' => $page_details))->render();

            if (config('coaster::admin.publishing') && !empty($page_details->id)) {

                $versions_table = self::version_table($page_details->id);

                $tab_headers[-1] = 'Versions';
                $tab_contents[-1] = View::make('coaster::partials.tabs.versions.main', array('content' => $versions_table))->render();

                $requests = PagePublishRequests::all_requests($page_details->id, ['status' => 'awaiting']);
                $tab_headers[-2] = 'Publish Requests';
                if ($requests->isEmpty()) {
                    $requests = 'No awaiting requests';
                }

                $version_id = self::$current_version ?: PageVersion::latest_version($page_details->id);

                $requests_table = View::make('coaster::partials.tabs.publish_requests.table', array('show' => ['page' => false, 'status' => true, 'requested_by' => true], 'requests' => $requests))->render();
                $tab_contents[-2] = View::make('coaster::partials.tabs.publish_requests.main', array('requests_table' => $requests_table, 'version_id' => $version_id));
            }

        }

        if (!empty($blocks)) {

            $categories = BlockCategory::orderBy('order', 'asc')->get();
            $page_id = !empty($page_details) ? $page_details->id : 0;

            foreach ($categories as $category) {
                if (!empty($blocks[$category->id])) {

                    $tab_headers[$tab_index] = $category->name;
                    $tab_contents[$tab_index] = '';

                    foreach ($blocks[$category->id] as $block) {
                        if (isset($block_contents[$block->id][Language::current()])) {
                            $block_content = $block_contents[$block->id][Language::current()]->content;
                        } else {
                            $block_content = '';
                        }
                        $tab_contents[$tab_index] .= self::get_field($block, $block_content, $page_id);
                    }

                    $tab_index++;

                }
            }

        }

        return array(
            'headers' => View::make('coaster::partials.tabs.header', array('tabs' => $tab_headers))->render(),
            'contents' => View::make('coaster::partials.tabs.content', array('tabs' => $tab_contents, 'item' => $item, 'button' => $button, 'can_publish' => Auth::action('pages.version-publish')))->render()
        );
    }

    public static function version_table($page_id)
    {
        $versionsQuery = PageVersion::with('user')->where('page_id', '=', $page_id)->orderBy('version_id', 'desc');
        $versions = $versionsQuery->paginate(15);
        $pagination = PaginatorRender::run($versions, config('coaster::admin.bootstrap_version'));

        $page_lang = PageLang::where('page_id', '=', $page_id)->where('language_id', '=', Language::current())->first();
        $live_version = $page_lang ? $page_lang->live_version : 0;

        $can_publish = Auth::action('pages.version-publish', ['page_id' => $page_id]);

        return View::make('coaster::partials.tabs.versions.table', array('versions' => $versions, 'pagination' => $pagination, 'live_version' => $live_version, 'can_publish' => $can_publish))->render();
    }

    public static function get_field(Block $block, $block_content, $page_id, $repeater_info = null)
    {
        $block_class = $block->get_class();
        $field_content = $block_class::edit($block, $block_content, $page_id, $repeater_info);

        if ($block_class::$edit_display_raw) {

            return $field_content;

        } else {

            $field_key = self::_get_input_key($block_class, $repeater_info);
            $parent_repeater = !empty($repeater_info) ? serialize($repeater_info) : null;

            return CmsBlockInput::make($block->type, array('name' => $field_key, 'content' => $field_content, 'block_id' => $block->id, 'label' => $block->label, 'page_id' => $page_id, 'parent_repeater' => $parent_repeater, 'input_id' => $block_class::$edit_id, 'extra_data' => $block_class::$extra_data));
        }
    }

    private static function _get_input_key($block_class, $repeater_info)
    {
        $field_key = '';
        if (!empty($block_class::$edit_id)) {

            foreach ($block_class::$edit_id as $segment) {
                $field_key .= '[' . $segment . ']';
            }

            if (!empty($repeater_info)) {
                $block_key = Blocks\Repeater::$blocks_key . '[' . $repeater_info->repeater_id . '][' . $repeater_info->row_id . '][' . $block_class::$blocks_key . ']';
            } else {
                $block_key = $block_class::$blocks_key;
            }

            $field_key = $block_key . $field_key;

        }
        return $field_key;
    }

    public static function process_submission($page_id = 0)
    {
        // update all text inputs
        self::submit_text($page_id);
        // update custom data (block submit functions)
        self::submit_custom_block_data($page_id);
        // update repeater inputs (not in custom submit as to only run once)
        Blocks\Repeater::submit($page_id, Blocks\Repeater::$blocks_key);
    }

    public static function submit_text($page_id, $repeater_key = null, $repeater_info = null)
    {
        $updated_text_blocks = Request::input($repeater_key . Blocks\_Base::$blocks_key);

        if (!empty($updated_text_blocks)) {
            foreach ($updated_text_blocks as $block_id => $block_content) {
                $block_class = Block::preload($block_id)->get_class();
                if ($block_class::$blocks_key == 'block') {
                    $block_content = $block_class::save($block_content);
                    self::update_block($block_id, $block_content, $page_id, $repeater_info);
                }
            }
        }
    }

    public static function submit_custom_block_data($page_id, $repeater_key = null, $repeater_info = null)
    {
        foreach (self::getBlockClasses() as $blockName => $blockClass) {
            if ($blockName != 'repeater') {
                $blockClass::submit($page_id, $repeater_key . $blockClass::$blocks_key, $repeater_info);
            }
        }
    }

    public static function getBlockClasses($reload = false)
    {
        if (!isset(self::$_blockClasses) || $reload) {
            $paths = [
                'CoasterCms\\Libraries\\Blocks\\' => base_path('vendor/web-feet/coasterframework/src/Libraries/Blocks'),
                'App\\' => base_path('app/Blocks')
            ];

            self::$_blockClasses = [];
            foreach ($paths as $classPath => $dirPath) {
                if (is_dir($dirPath)) {
                    foreach (scandir($dirPath) as $file) {
                        $className = explode('.', $file)[0];
                        if (!empty($className) && $className != '_Base') {
                            self::$_blockClasses[trim(strtolower($className), '_')] = $classPath . $className;
                        }
                    }
                }
            }
        }

        return self::$_blockClasses;
    }

    public static function get_data_for_version($model, $version, $filter_on = array(), $filter_values = array(), $order_by = null)
    {
        $parameters = [];
        $where_qs['j'] = [];
        $where_qs['main'] = array('main.content != ""');
        if (!empty($filter_on) && !empty($filter_values)) {
            $parameters = array();
            foreach ($filter_values as $k => $filter_value) {
                if (!empty($filter_value)) {
                    if (is_array($filter_value)) {
                        $i = 1;
                        $vars1 = array();
                        $vars2 = array();
                        foreach ($filter_value as $value) {
                            $parameters['fid1_' . $k . $i] = $value;
                            $vars1[] = ':fid1_' . $k . $i;
                            $parameters['fid2_' . $k . $i] = $value;
                            $vars2[] = ':fid2_' . $k . $i;
                            $i++;
                        }
                        $eq1 = "IN (" . implode(", ", $vars1) . ")";
                        $eq2 = "IN (" . implode(", ", $vars2) . ")";

                    } else {
                        $parameters['fid1' . $k] = $filter_value;
                        $parameters['fid2' . $k] = $filter_value;
                        $eq1 = '= :fid1' . $k;
                        $eq2 = '= :fid2' . $k;
                    }
                    $where_qs['j'][] = 'inr.' . $filter_on[$k] . ' ' . $eq1;
                    $where_qs['main'][] = 'main.' . $filter_on[$k] . ' ' . $eq2;
                } else {
                    return null;
                }
            }
        }
        if (!empty($version) && $version > 0) {
            $where_qs['j'][] = 'version <= :version';
            $parameters['version'] = $version;
        }
        foreach ($where_qs as $k => $where_q) {
            if (!empty($where_q)) {
                $where_qs[$k] = 'where ' . implode(' and ', $where_q);
            } else {
                $where_qs[$k] = '';
            }
        }
        $on_clause = 'main.version = j.version';
        $max_live = '';
        $table_name = $model->getTable();
        $full_table_name = DB::getTablePrefix() . $table_name;
        switch ($table_name) {
            case 'page_blocks':
                $max_live = ($version == -1) ? 'join page_lang pl on pl.page_id = inr.page_id and pl.language_id = inr.language_id and pl.live_version >= inr.version' : '';
                $identifiers = array('block_id', 'language_id', 'page_id');
                break;
            case 'page_blocks_default':
                $identifiers = array('block_id', 'language_id');
                break;
            case 'page_blocks_repeater_data':
                $identifiers = array('block_id', 'row_key');
                break;
            default:
                throw new \Exception('unknown blocks data table: ' . $full_table_name);
        }
        foreach ($identifiers as $identifier) {
            $on_clause .= ' and main.' . $identifier . ' = j.' . $identifier;
        }
        $select_identifiers = 'inr.' . implode(', inr.', $identifiers);
        $order_by = !empty($order_by) ? ' order by main.' . $order_by : '';
        $correct_versions_query = "
            select main.* from " . $full_table_name . " main
            inner join(
                select " . $select_identifiers . ", max(inr.version) version from " . $full_table_name . " inr " . $max_live . "
                " . $where_qs['j'] . "
                group by " . $select_identifiers . "
            ) j on " . $on_clause . "
            " . $where_qs['main'] . $order_by;
        return DB::select(DB::raw($correct_versions_query), $parameters);
    }

}