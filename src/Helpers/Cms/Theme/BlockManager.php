<?php namespace CoasterCms\Helpers\Cms\Theme;

use Auth;
use CoasterCms\Helpers\Cms\Page\Path;
use CoasterCms\Helpers\Cms\View\PaginatorRender;
use CoasterCms\Models\BlockBeacon;
use CoasterCms\Models\BlockCategory;
use CoasterCms\Models\Language;
use CoasterCms\Models\Menu;
use CoasterCms\Models\MenuItem;
use CoasterCms\Models\Page;
use CoasterCms\Models\PageGroup;
use CoasterCms\Models\PageLang;
use CoasterCms\Models\PagePublishRequests;
use CoasterCms\Models\PageVersion;
use CoasterCms\Models\Template;
use CoasterCms\Models\Theme;
use View;

class BlockManager
{

    public static $to_version = 0;
    public static $current_version = 0;
    public static $publish = false;

    public static function tab_contents($blocks, $block_contents, $item, $page = null, $page_lang = null)
    {
        $tab_headers = array();
        $tab_contents = array();

        $page_id = !empty($page) ? $page->id : 0;
        $publishingOn = (config('coaster::admin.publishing') > 0) ? true : false;
        $canPublish = ($publishingOn && Auth::action('pages.version-publish', ['page_id' => $page_id])) || !$publishingOn;

        if (!empty($page)) {

            $tab_headers[0] = 'Page Info';
            $tab_contents[0] = '';
            
            // page parent (only updated for new pages)
            if (empty($page_id)) {
                $parentPages = [-1 => '-- None --', 0 => '-- Top Level Page --'] + Page::get_page_list(['links' => false, 'exclude_home' => true, 'group_pages' => false]);
                if (!array_key_exists($page->parent, $parentPages)) {
                    $page->parent = -1;
                }
            } else {
                $parentPages = null;
            }

            // beacons selection (only updated for existing pages)
            if (Auth::action('themes.beacons-update') && !empty($page_id)) {
                $beaconSelect = BlockBeacon::getDropdownOptions($page_id);
                $beaconSelect = empty($beaconSelect->options) ? null : $beaconSelect;
            } else {
                $beaconSelect = null;
            }

            // page name, url
            $fullUrls = [-1 => '?', 0 => '/'];
            foreach (Path::all() as $pageId => $details) {
                $fullUrls[$pageId] = rtrim($details->fullUrl, '/') . '/';
            }
            $urlPrefixes = $page->parentPathIds();
            $tab_contents[0] .= View::make('coaster::partials.tabs.page_info.page_info', ['page' => $page, 'page_lang' => $page_lang, 'beacon_select' => $beaconSelect, 'parentPages' => $parentPages, 'urlArray' => $fullUrls, 'urlPrefixes' => $urlPrefixes, 'publishing_on' => $publishingOn, 'can_publish' => $canPublish])->render();

            // groups
            $groups = PageGroup::all();
            if (!$groups->isEmpty() || config('coaster::site.groups') !== '') {
                $tab_contents[0] .= View::make('coaster::partials.tabs.page_info.groups', ['page' => $page, 'page_lang' => $page_lang, 'groups' => $groups])->render();
            }

            //template
            $templateData = Template::find($page->template);
            $templates = Theme::get_template_list($page->template);
            $templateSelectHidden = !empty($templateData) ? $templateData->hidden : false;

            // menu selection
            $menus = Menu::all();
            if (!$menus->isEmpty() && Auth::action('menus')) {
                if (!empty($page_id)) {
                    $in_menus = MenuItem::get_page_menus($page_id);
                } else {
                    $in_menus = [];
                }
                foreach ($menus as $k => $menu) {
                    if (in_array($menu->id, $in_menus)) {
                        $menus[$k]->in_menu = true;
                    } else {
                        $menus[$k]->in_menu = false;
                    }
                }
            } else {
                $menus = [];
            }

            $tab_contents[0] .= View::make('coaster::partials.tabs.page_info.display_info', ['page' => $page, 'template' => $page->template, 'templates' => $templates, 'templateSelectHidden' => $templateSelectHidden, 'menus' => $menus, 'can_publish' => $canPublish])->render();

            // live options, sitemap
            $liveOptions = [0 => 'Not Live (Hidden)', 1 => 'Live (Ignores Dates)', 2 => 'Live Between Specific Dates/Times'];
            $sitemapOptions = [0 => 'Excluded From Sitemap', 1 => 'Included in Sitemap (If Page Live)'];
            $tab_contents[0] .= View::make('coaster::partials.tabs.page_info.live_options', ['page' => $page, 'liveOptions' => $liveOptions, 'sitemapOptions' => $sitemapOptions, 'disabled' => !$canPublish])->render();

            if (config('coaster::admin.publishing') && !empty($page_id) && !empty($blocks)) {

                $versions_table = PageVersion::version_table($page_id);

                $tab_headers[-1] = 'Versions';
                $tab_contents[-1] = View::make('coaster::partials.tabs.versions.main', ['content' => $versions_table])->render();

                $allRequests = PagePublishRequests::all_requests($page_id);
                if (!$allRequests->isEmpty()) {
                    $awaitingRequests = PagePublishRequests::all_requests($page_id, ['status' => 'awaiting']);
                    $tab_headers[-2] = 'Publish Requests';
                    if ($count = $awaitingRequests->count()) {
                        $tab_headers[-2] .= ' &nbsp; <span class="badge">'.$count.'</span>';
                    }
                    if ($awaitingRequests->isEmpty()) {
                        $awaitingRequests = 'No awaiting requests';
                    }

                    $version_id = self::$current_version ?: PageVersion::latest_version($page_id);

                    $requests_table = View::make('coaster::partials.tabs.publish_requests.table', ['show' => ['page' => false, 'status' => true, 'requested_by' => true], 'requests' => $awaitingRequests])->render();
                    $tab_contents[-2] = View::make('coaster::partials.tabs.publish_requests.main', ['requests_table' => $requests_table, 'version_id' => $version_id]);
                }
            }

        }

        $hideUpdate = false;
        if (!empty($blocks)) {
            $categories = BlockCategory::orderBy('order', 'asc')->get();
            $tab_index = 1;
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
                        $tab_contents[$tab_index] .= $block->setPageId($page_id)->getTypeObject()->edit($block_content);
                    }
                    $tab_index++;
                }
            }
        } elseif (!empty($page) && $page->link == 1 && !$canPublish) {
            $hideUpdate = true;
        }

        $updateOnly = empty($page) || (!empty($page) && $page->link != 0);

        return array(
            'headers' => View::make('coaster::partials.tabs.header', array('tabs' => $tab_headers))->render(),
            'contents' => View::make('coaster::partials.tabs.content', array('tabs' => $tab_contents, 'item' => $item, 'new_page' => !$page_id,  'updateOnly' => $updateOnly,  'hideUpdate' => $hideUpdate, 'publishing' => $publishingOn, 'can_publish' => $canPublish, 'page' => $page))->render()
        );
    }

}