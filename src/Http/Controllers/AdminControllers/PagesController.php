<?php namespace CoasterCms\Http\Controllers\AdminControllers;

use Auth;
use CoasterCms\Helpers\Cms\DateTimeHelper;
use CoasterCms\Helpers\Cms\Page\Path;
use CoasterCms\Libraries\Builder\FormMessage;
use CoasterCms\Helpers\Cms\View\PaginatorRender;
use CoasterCms\Http\Controllers\AdminController;
use CoasterCms\Models\AdminLog;
use CoasterCms\Models\Block;
use CoasterCms\Models\BlockBeacon;
use CoasterCms\Models\Language;
use CoasterCms\Models\MenuItem;
use CoasterCms\Models\Page;
use CoasterCms\Models\PageBlock;
use CoasterCms\Models\PageGroup;
use CoasterCms\Models\PageGroupPage;
use CoasterCms\Models\PageLang;
use CoasterCms\Models\PagePublishRequests;
use CoasterCms\Models\PageSearchData;
use CoasterCms\Models\PageVersion;
use CoasterCms\Models\PageVersionSchedule;
use CoasterCms\Models\Template;
use CoasterCms\Models\Theme;
use CoasterCms\Models\UserRole;
use Request;
use View;

class PagesController extends AdminController
{

    public function getIndex()
    {
        $rootPages = Page::join('page_lang', 'page_lang.page_id', '=', 'pages.id')->where(function ($query) {
            $query->whereIn('page_lang.url', ['', '/']);
        })->where('page_lang.language_id', '=', Language::current())->where('link', '=', 0)->get(['pages.*'])->all();
        $rootPageIds = array_map(function($rootPage) {return 'list_'.$rootPage->id;}, $rootPages);

        $this->layoutData['content'] = View::make('coaster::pages.pages', array('pages' => Page::getPageTreeView(0), 'add_page' => Auth::action('pages.add'), 'page_states' => Auth::user()->getPageStates(), 'max' => Page::at_limit(), 'rootPageIds' => $rootPageIds, 'groups_exist' => PageGroup::count()));
        $this->layoutData['modals'] = View::make('coaster::modals.general.delete_item');
    }

    public function getAdd($pageId = 0, $groupId = 0)
    {
        $this->layoutData['content'] = $this->_load_page_data(0, 0, ['parentPageId' => $pageId, 'groupId' => $groupId]);
    }

    public function postAdd($pageId = 0, $groupId = 0)
    {
        $new_page_id = $this->_save_page_info();
        if ($new_page_id === false) {
            $this->getAdd($pageId);
            return null;
        } else {
            return \redirect()->route('coaster.admin.pages.edit', ['pageId' => $new_page_id]);
        }
    }

    public function getEdit($pageId, $versionId = 0)
    {
        $this->layoutData['content'] = $this->_load_page_data($pageId, $versionId);
        return null;
    }

    public function postEdit($pageId)
    {
        $existingPage = Page::find($pageId);

        $publish = false;
        $publishing = (bool) config('coaster::admin.publishing');
        $canPublish = Auth::action('pages.version-publish', ['page_id' => $pageId]);
        if ($publishing && $existingPage->link == 0) {
            // check if publish
            if (Request::input('publish') != '' && $canPublish) {
                $publish = true;
                // check if there were requests to publish the version being edited
                $overwriting_page_version = PageVersion::where('version_id', '=', Request::input('versionFrom'))->where('page_id', '=', $pageId)->first();
                $requests = PagePublishRequests::where('page_version_id', '=', $overwriting_page_version->id)->where('status', '=', 'awaiting')->get();
                if (!$requests->isEmpty()) {
                    foreach ($requests as $request) {
                        $request->status = 'approved';
                        $request->mod_id = Auth::user()->id;
                        $request->save();
                    }
                }
            }
        } elseif (!$publishing || ($existingPage->link == 1 && $canPublish)) {
            $publish = true;
        }

        // run if duplicate button was hit
        if (Request::input('duplicate') == 1) {
            if ($existingPage->canDuplicate()) {
                $new_page_id = $this->_save_page_info(0, 1, $existingPage);
                if ($new_page_id === false) {
                    $this->setAlert('danger', 'Error: Duplication failed');
                    return $this->getEdit($pageId);
                } else {
                    Block::submit($new_page_id, 1, $publish);
                    return \redirect()->route('coaster.admin.pages.edit', ['pageId' => $new_page_id]);
                }
            } else {
                return abort(403, 'Action not permitted');
            }
        }

        $version = PageVersion::add_new($pageId);

        // update blocks
        Block::submit($pageId, $version->version_id, $publish);
        if ($publish) {
            if (Request::input('publish_request') != '') {
                PagePublishRequests::add($pageId, $version->version_id, Request::input('request_note'));
            }
            $version->publish();
        }

        // save page info
        if ($this->_save_page_info($pageId, $version->version_id) === false) {
            $this->setAlert('warning', 'Error: "Page Info" not updated (check tab for errors)');
        } else {
            $this->setAlert('success', 'Page Content Updated');
        }

        // display page edit form
        return $this->getEdit($pageId, $version->version_id);
    }

    public function postSort()
    {
        $pages = Request::input('list');
        if (!empty($pages)) {

            $rootPages = Page::join('page_lang', 'page_lang.page_id', '=', 'pages.id')->where(function ($query) {
                $query->whereIn('page_lang.url', ['', '/']);
            })->where('page_lang.language_id', '=', Language::current())->where('link', '=', 0)->get(['pages.*'])->all();
            $rootPageIds = array_map(function($rootPage) {return $rootPage->id;}, $rootPages);
            $order = [];
            $changeUnderParentIds = [];

            foreach ($pages as $pageId => $parent) {
                $currentPage = Page::preload($pageId);
                if ($currentPage->exists) {

                    $parent = (empty($parent) || $parent == 'null') ? 0 : $parent;
                    if ($currentPage->parent != $parent && $parent != 0 && (in_array($currentPage->id, $rootPageIds) || in_array($parent, $rootPageIds))) {
                        return 0; // don't allow root/home page to be moved under other pages or other pages to be moved under it
                    }

                    // get the order value for current page
                    $order[$parent] = isset($order[$parent]) ? $order[$parent] : 0;
                    $order[$parent]++;

                    if (($currentPage->parent != $parent || $currentPage->order != $order[$parent])) {
                        if (Auth::action('pages.sort', ['page_id' => $parent]) && Auth::action('pages.sort', ['page_id' => $currentPage->parent])) {
                            $parentPageName = $parent ? PageLang::preload($parent)->name : 'top level';
                            $pageName = PageLang::preload($pageId)->name;
                            if ($parent != $currentPage->parent) {
                                array_push($changeUnderParentIds, $parent, $currentPage->parent);
                                AdminLog::new_log('Moved page \'' . $pageName . '\' under \'' . $parentPageName . '\' (Page ID ' . $currentPage->id . ')');
                            }
                            if (!in_array($parent, $changeUnderParentIds)) {
                                $changeUnderParentIds[] = $parent;
                                AdminLog::new_log('Re-ordered pages in \'' . $parentPageName . '\' (Page ID ' . $currentPage->id . ')');
                            }
                            $changeUnderParentIds = array_unique($changeUnderParentIds);
                            $currentPage->parent = $parent;
                            $currentPage->order = $order[$parent];
                            $currentPage->save();
                        } else {
                            return 0; // error, can't move page to new location
                        }
                    }

                } else {
                    return 0; // error, moved page no longer exists
                }
            }

        }
        return 1;
    }

    public function postDelete($pageId)
    {
        $page = Page::find($pageId);
        if (!empty($page)) {
            // backup/delete
            $log_id = $page->delete();
            return $log_id;
        }
        return 0;
    }

    public function postVersions($pageId)
    {
        return PageVersion::version_table($pageId);
    }

    public function postVersionSchedule($pageId)
    {
        $publishingOn = (config('coaster::admin.publishing') > 0) ? true : false;
        if (!$publishingOn || !Auth::action('pages.version-publish', ['page_id' => $pageId])) {
            return 0;
        }

        $scheduledVersionId = Request::input('remove');
        if (!empty($scheduledVersionId)) {
            $scheduledVersion = PageVersionSchedule::find($scheduledVersionId);
            if (!empty($scheduledVersion)) {
                $scheduledVersion->delete();
                return 1;
            } else {
                return 0;
            }
        }

        $scheduleFrom = DateTimeHelper::jQueryToMysql(Request::input('schedule_from'));
        $scheduleTo = DateTimeHelper::jQueryToMysql(Request::input('schedule_to'));
        $scheduleToVersion = Request::input('schedule_to_version');
        $scheduleRepeat = Request::input('schedule_repeat')?:0;
        $versionId = Request::input('version_id');
        $pageVersion = PageVersion::where('page_id', '=', $pageId)->where('version_id', '=', $versionId)->first();

        if (!empty($pageVersion) && !empty($scheduleFrom)) {

            $pageVersionSchedule = new PageVersionSchedule;
            $pageVersionSchedule->page_version_id = $pageVersion->id;
            $pageVersionSchedule->live_from = $scheduleFrom;
            if (is_numeric($scheduleRepeat)) {
                $pageVersionSchedule->repeat_in = $scheduleRepeat;
            } else {
                $pageVersionSchedule->repeat_in_func = $scheduleRepeat;
            }
            $pageVersionSchedule->save();

            if (!empty($scheduleTo) && !empty($scheduleToVersion)) {

                $pageVersion = PageVersion::where('page_id', '=', $pageId)->where('version_id', '=', $scheduleToVersion)->first();
                if (!empty($pageVersion)) {
                    $pageVersionSchedule = new PageVersionSchedule;
                    $pageVersionSchedule->page_version_id = $pageVersion->id;
                    $pageVersionSchedule->live_from = $scheduleTo;
                    if (is_numeric($scheduleRepeat)) {
                        $pageVersionSchedule->repeat_in = $scheduleRepeat;
                    } else {
                        $pageVersionSchedule->repeat_in_func = $scheduleRepeat;
                    }
                    $pageVersionSchedule->save();
                }

            }
            return 1;
        }

        return 0;
    }

    public function postVersionRename($pageId)
    {
        $version_name = Request::input('version_name');
        $version_id = Request::input('version_id');
        if (!empty($pageId) && !empty($version_id)) {
            $page_version = PageVersion::where('page_id', '=', $pageId)->where('version_id', '=', $version_id)->first();
            if (!empty($page_version) && ($page_version->user_id == Auth::user()->id || Auth::action('pages.version-publish', ['page_id' => $pageId]))) {
                $page_version->label = $version_name;
                $page_version->save();
                return 1;
            }
        }
        return 0;
    }

    public function postVersionPublish($pageId)
    {
        $version_id = Request::input('version_id');
        if (!empty($pageId) && !empty($version_id)) {
            $page_version = PageVersion::where('page_id', '=', $pageId)->where('version_id', '=', $version_id)->first();
            if (!empty($page_version)) {
                return $page_version->publish();
            }
        }
        return 0;
    }

    public function postRequests($pageId)
    {
        if (empty($pageId)) {
            // block access to all requests
            return 0;
        }

        $type = Request::input('request_type');
        $type = $type ? ['status' => $type] : [];

        $show = Request::input('request_show');
        $show = $show ?: ['page' => false, 'status' => true, 'requested_by' => true];


        $requests = PagePublishRequests::all_requests($pageId, $type, 25);
        if ($requests->isEmpty()) {
            $requests = 'No awaiting requests';
            $pagination = '';
        } else {
            $pagination = PaginatorRender::admin($requests);
        }
        return View::make('coaster::partials.tabs.publish_requests.table', array('show' => $show, 'requests' => $requests, 'pagination' => $pagination))->render();

    }

    public function postRequestPublish($pageId)
    {
        $version_id = Request::input('version_id');
        $note = Request::input('note');
        return PagePublishRequests::add($pageId, $version_id, $note);
    }

    public function postRequestPublishAction($pageId)
    {
        $request_id = Request::input('request');
        $request = PagePublishRequests::with('page_version')->find($request_id);
        if (!empty($request)) {
            $request_action = Request::input('request_action');
            return $request->process($request_action);
        } else {
            return 0;
        }
    }

    public function getTinymcePageList()
    {
        $pages = array();
        $all_pages = Page::all();
        foreach ($all_pages as $page) {
            if (config('coaster::admin.advanced_permissions') && !Auth::action('pages', ['page_id' => $page->id])) {
                continue;
            }
            $pages[] = $page->id;
        }
        $page_details = Path::getFullPaths($pages, html_entity_decode(' &raquo; '));
        $json_array = array();
        foreach ($page_details as $page_detail) {
            $details = new \stdClass;
            $details->title = $page_detail->fullName;
            $details->value = $page_detail->fullUrl;
            $json_array[] = $details;
        }
        usort($json_array, function ($a, $b) {
            return strcmp($a->title, $b->title);
        });
        return json_encode($json_array);
    }

    private function _save_page_info($pageId = 0, $versionId = 0, $existingPage = null)
    {
        $input = Request::all();
        $page_info = $input['page_info'];
        $page_info_lang = $input['page_info_lang'];
        $canPublish = (config('coaster::admin.publishing') > 0 && Auth::action('pages.version-publish', ['page_id' => $pageId])) || config('coaster::admin.publishing') == 0;

        foreach ($page_info as $k => $page_info_field) {
            if (is_array($page_info_field) && array_key_exists('exists', $page_info_field)) {
                $page_info[$k] = array_key_exists('select', $page_info_field) ? $page_info_field['select'] : 0;
            }
        }

        /*
         * Load missing page & page_lang request data from db or use defaults
         */
        if ($pageId || $existingPage) {
            if ($existingPage) {
                $page = $existingPage->replicate();
                $page->setRelations([]);
            } else {
                $page = Page::find($pageId);
            }
            if (empty($page) || (!$existingPage && !$page->id)) {
                throw new \Exception('page not found');
            }
            foreach ($page->getAttributes() as $attribute => $value) {
                if (!in_array($attribute, ['updated_at', 'created_at']) && !isset($page_info[$attribute])) {
                    $page_info[$attribute] = $page->$attribute;
                }
            }

            $page_lang = PageLang::preload($page->id);
            foreach ($page_lang->getAttributes() as $attribute => $value) {
                if (!in_array($attribute, ['updated_at', 'created_at']) && !isset($input['page_info_lang'][$attribute])) {
                    $input['page_info_lang'][$attribute] = $page_lang->$attribute;
                }
            }
        } else {
            $page = new Page;
            $page_lang = new PageLang;
            $page_info = array_merge([
                'template' => 0,
                'parent' => -1,
                'child_template' => 0,
                'order' => 0,
                'group_container' => 0,
                'link' => 0,
                'live' => 0,
                'sitemap' => 1,
                'live_start'=> null,
                'live_end' => null
            ], $page_info);
            $page_info_lang = array_merge([
                'url' => '/',
                'name' => ''
            ], $page_info_lang);
        }
        if ($existingPage) {
            $page_info_lang['name'] = preg_replace('/\s+Duplicate$/', '', $page_info_lang['name']) . ' Duplicate';
            $page_info_lang['url'] = preg_replace('/--v\w+$/', '', $page_info_lang['url']) . '--v' . base_convert(microtime(true), 10, 36);
        }
        $page_info_other = !empty($input['page_info_other'])?$input['page_info_other']:[];
        $page_groups = !empty($input['page_groups'])?$input['page_groups']:[];

        // page limit check
        if (!empty($page_info['link']) && Page::at_limit($page_info['parent'] == -1)) {
            return false;
        }

        /*
         * Save Page
         */
        $parent = Page::find($page_info['parent']);
        if ($page_info['parent'] > 0 && (!$parent || $parent->parent == -1)) {
            return false;
        }

        $siblings = [];
        foreach ($page_groups as $pageGroupId => $checkedVal) {
            $pageGroup = PageGroup::preload($pageGroupId);
            $siblings = array_merge($pageGroup->exists ? $pageGroup->itemPageIds() : [], $siblings);
        }
        if ($page_info['parent'] >= 0) {
            $siblings = array_merge(Page::getChildPageIds($page_info['parent']), $siblings);
            if (isset($page->order)) {
                $page_info['order'] = $page->order;
            } else {
                $page_order = Page::where('parent', '=', $page_info['parent'])->orderBy('order', 'desc')->first();
                if (!empty($page_order)) {
                    $page_info['order'] = $page_order->order + 1;
                }
            }
        }
        $siblings = array_unique($siblings);

        $versionTemplate = $page_info['template'];
        if (empty($input['publish']) && $page->id) {
            $page_info['template'] = $page->template;
        }
        if ($page_info['link'] == 1) {
            $page_info['template'] = 0;
        }

        if ($page_info['live'] == 2) {
            if (!$page_info['live_start'] && !$page_info['live_end']) {
                $page_info['live'] = 0;
            }
        }
        $page_info['live_start'] = DateTimeHelper::jQueryToMysql($page_info['live_start']);
        $page_info['live_end'] = DateTimeHelper::jQueryToMysql($page_info['live_end']);

        $createNewGroup = ($page_info['group_container'] == -1);
        $page_info['group_container'] = $createNewGroup ? 0 : $page_info['group_container'];
        $page_info['group_container_url_priority'] = !empty($page_info['group_container_url_priority']) ? $page_info['group_container_url_priority'] : 0;
        if ($page_info['group_container']) {
            $groupContainer = PageGroup::preload($page_info['group_container']);
            if (!$groupContainer->exists || !$groupContainer->canAddContainers()) {
                $page_info['group_container'] = 0;
            }
        }

        if (!$canPublish) {
            if (!$page->id) {
                $page_info['live'] = 0;
            } else {
                foreach ($page_info as $attribute => $value) {
                    if (!in_array($attribute, ['updated_at', 'created_at'])) {
                        $page_info[$attribute] = $page->$attribute;
                    }
                }
            }
        }

        foreach ($page_info as $attribute => $value) {
            if (!in_array($attribute, ['updated_at', 'created_at']) && isset($page_info[$attribute])) {
                $page->$attribute = ($page_info[$attribute] !== '' ? $page_info[$attribute] : null);
            }
        }

        // delay save until after page land checks
        // $page->save();

        /*
         * Save Page Lang
         */
        if ($page_info_lang['name'] == '') {
            FormMessage::add('page_info_lang[name]', 'page name required');
            return false;
        }

        $page_info_lang['url'] = trim($page_info_lang['url']);
        if ($page->link == 0) {
            $page_info_lang['url'] = strtolower(str_replace(['/', ' '], '-', $page_info_lang['url']));
        }
        if (preg_match('#^[-]+$#', $page_info_lang['url'])) {
            $page_info_lang['url'] = '';
        }
        if ($page_info_lang['url'] == '' && ($page_info['parent'] == 0)) {
            $page_info_lang['url'] = '/';
        }

        if ($page_info_lang['url'] == '') {
            FormMessage::add('page_info_lang[url]', 'page url required');
            return false;
        }

        if (!empty($siblings) && $page_info['link'] == 0) {
            $same_level = PageLang::where('url', '=', $page_info_lang['url'])->whereIn('page_id', $siblings);
            $same_level = $page->id ? $same_level->where('page_id', '!=', $page->id) : $same_level;
            if (!$same_level->get()->isEmpty()) {
                FormMessage::add('page_info_lang[url]', 'url in use by another page!');
                return false;
            }
        }

        $page->save();

        if ($canPublish || !$pageId) {
            $page_lang->page_id = $page_lang->page_id ?: $page->id;
            $page_lang->language_id = Language::current();
            $page_lang->url = $page_info_lang['url'];
            $page_lang->name = $page_info_lang['name'];
            $page_lang->live_version = $page_lang->live_version ?: 1;
            $page_lang->save();
        }

        if (!$pageId) {
            if ($title_block = Block::where('name', '=', config('coaster::admin.title_block'))->first()) {
                $title_block->setPageId($page->id)->getTypeObject()->save($page_lang->name); // saves first page version
            }
        }
        PageSearchData::updateText(strip_tags($page_lang->name), 0, $page->id);

        /*
         * Save Groups
         */
        $currentGroupIds = $page->groupIds();
        $newGroupIds = array_keys($page_groups);
        PageGroupPage::where('page_id', '=', $page->id)->whereIn('group_id', array_diff($currentGroupIds, $newGroupIds))->delete();
        foreach (array_diff($newGroupIds, $currentGroupIds) as $addGroupId) {
            $page->groups()->attach($addGroupId);
        }

        /*
         * Save Page Version
         */
        if ($pageId) {
            // save page versions template
            $page_version = PageVersion::where('page_id', '=', $page->id)->where('version_id', '=', $versionId)->first();
            $page_version->template = $versionTemplate;
            $page_version->save();
        } elseif ($existingPage) {
            // duplicate role actions from original page
            foreach (UserRole::all() as $role) {
                $page_actions = $role->page_actions()->where('page_id', '=', $existingPage->id)->get();
                if (!empty($page_actions)) {
                    foreach ($page_actions as $page_action) {
                        $role->page_actions()->attach($page->id, ['action_id' => $page_action->pivot->action_id, 'access' => $page_action->pivot->access]);
                    }
                }
            }
        }

        /*
         * Save Menu Item
         */
        if ($canPublish || !$pageId) {
            // set menu options
            if (Auth::action('menus')) {
                $menus = !empty($page_info_other['menus']) ? $page_info_other['menus'] : [];
                MenuItem::set_page_menus($page->id, $menus);
            }
        }

        /*
         * Save Beacons
         */
        if (Auth::action('themes.beacons-update')) {
            $existingBeacons = [];
            $setBeacons = BlockBeacon::where('page_id', '=', $page->id)->get();
            foreach ($setBeacons as $setBeacon) {
                $existingBeacons[$setBeacon->unique_id] = $setBeacon->unique_id;
            }
            if (!empty($existingBeacons)) {
                BlockBeacon::preload(); // check page relations (remove page id off beacons if url changed)
            }
            if (!empty($page_info_other['beacons'])) {
                foreach ($page_info_other['beacons'] as $uniqueId) {
                    if (!empty($existingBeacons[$uniqueId])) {
                        unset($existingBeacons[$uniqueId]);
                    }
                    BlockBeacon::updateUrl($uniqueId, $page->id);
                }
                foreach ($existingBeacons as $uniqueId) {
                    BlockBeacon::updateUrl($uniqueId, 0);
                }
            }
            if (!empty($input['page_info_other_exists']['beacons'])) {
                foreach ($existingBeacons as $uniqueId) {
                    BlockBeacon::updateUrl($uniqueId, 0);
                }
            }
        }

        /*
         * Create New Group
         */
        if ($createNewGroup) {
            $newGroup =  new PageGroup;
            $newGroup->name = $page_lang->name;
            $newGroup->item_name = 'Page';
            $newGroup->default_template = 0;
            $newGroup->save();
            $page->group_container = $newGroup->id;
            $page->save();
        }

        /*
         * Log and return saved page id
         */
        if (!$pageId) {
            AdminLog::new_log('Added page \'' . $page_lang->name . '\' (Page ID ' . $page->id . ')');
        } else {
            AdminLog::new_log('Updated page \'' . $page_lang->name . '\' (Page ID ' . $page->id . ')');
        }
        return $page->id;
    }

    private function _load_page_data($pageId = 0, $versionId = 0, $addPageInfo = [])
    {
        $blocks = [];
        $blocks_content = [];
        $auth = [];
        $versionData = [];
        $frontendLink = '';

        $publishingOn = config('coaster::admin.publishing') > 0;
        $auth['can_publish'] = ($publishingOn && Auth::action('pages.version-publish', ['page_id' => $pageId])) || (!$publishingOn && Auth::action('pages.edit', ['page_id' => $pageId]));

        if (!empty($pageId)) {

            // get page data
            $page = Page::find($pageId);
            if (empty($page)) {
                return 'Page Not Found';
            }
            $parent = Page::find($page->parent);
            PageVersionSchedule::checkPageVersionIds();

            // get page lang data
            $page_lang = PageLang::where('page_id', '=', $pageId)->where('language_id', '=', Language::current())->first();
            if (empty($page_lang)) {
                $page_lang = PageLang::where('page_id', '=', $pageId)->first();
                if (empty($page_lang)) {
                    return 'Page Lang Data Not Found';
                }
                $page_lang = $page_lang->replicate();
                $page_lang->language_id = Language::current();
                $page_lang->save();
            }
            $page_lang->url = ltrim($page_lang->url, '/');

            // get version data
            $versionData['latest'] = PageVersion::latest_version($pageId);
            $versionData['editing'] = ($versionId == 0 || $versionId > $versionData['latest']) ? $versionData['latest'] : $versionId;
            $versionData['live'] = $page_lang->live_version;

            // get frontend link (preview or direct link if document)
            $frontendLink = Path::getFullUrl($pageId);
            if (!$page->is_live() && $page->link == 0) {
                $live_page_version = PageVersion::where('page_id', '=', $pageId)->where('version_id', '=', $versionData['live'])->first();
                if (!empty($live_page_version)) {
                    $frontendLink .= '?preview=' . $live_page_version->preview_key;
                }
            }

            // if loading a non live version get version template rather than current page template
            if ($versionData['live'] != $versionData['editing']) {
                $page_version = PageVersion::where('version_id', '=', $versionData['editing'])->where('page_id', '=', $pageId)->first();
                if (empty($page_version)) {
                    return 'version not found';
                } else {
                    $page->template = $page_version->template;
                }
            }

            // load blocks and content
            if ($page->link == 0) {
                $theme = Theme::find(config('coaster::frontend.theme'));
                if (!empty($theme)) {
                    $blocks = Template::template_blocks($theme->id, $page->template);
                } else {
                    return 'active theme not found';
                }
                $blocks_content = PageBlock::preload_page($pageId, $versionData['editing']);
            }

            // check duplicate permission
            $auth['can_duplicate'] = $page->canDuplicate();

            // add required modals
            if ($publishingOn) {
                $intervals = PageVersionSchedule::selectOptions();
                $this->layoutData['modals'] =
                    View::make('coaster::modals.pages.publish')->render() .
                    View::make('coaster::modals.pages.publish_schedule', ['intervals' => $intervals, 'live_version' => $versionData['live']])->render() .
                    View::make('coaster::modals.pages.request_publish')->render() .
                    View::make('coaster::modals.pages.rename_version')->render();
            }

        } else {

            // set page data
            $page = new Page;
            if (!empty($addPageInfo['parentPageId']) && $parent = Page::find($addPageInfo['parentPageId'])) {
                $page->parent = $parent->id;
                $page->template = $parent->child_template;
            } else {
                $page->parent = 0;
            }
            if (!empty($addPageInfo['groupId']) && $group = PageGroup::find($addPageInfo['groupId'])) {
                $page->groups->add($group);
                $page->template = $group->default_template;
                $page->parent = empty($addPageInfo['parentPageId']) ? -1 : $page->parent;
            }
            $page->group_container = 0;
            $page->link = 0;
            if (!$auth['can_publish']) {
                $page->live = 0;
            } else {
                $page->live = 1;
            }
            $page->sitemap = 1;

            // set page lang data
            $page_lang = new PageLang;
            $page_lang->name = '';
            $page_lang->url = '';

        }

        // set group details if a group page
        if (!$page->groups->isEmpty()) {
            $item_name = $page->groupItemsNames() ?: 'Page';
        } else {
            $item_name = 'Page';
        }

        // load child template from parent page template
        if (empty($page->template) && !empty($parent)) {
            $parent_template = Template::find($parent->template);
            if (!empty($parent_template)) {
                $page->template = $parent_template->child_template;
            }
        }

        // get default template if not still note set above
        if (empty($page->template)) {
            $page->template = config('coaster::admin.default_template');
        }

        list($tab_headers, $tab_contents) = Block::getTabs($blocks, $blocks_content, $page->id);

        list($tab_headers[0], $tab_contents[0]) = $page->tabInfo();
        ksort($tab_headers);

        if ($publishingOn && $page->id && !empty($blocks)) {
            $tab_headers[-1] = 'Versions';
            $tab_contents[-1] = View::make('coaster::partials.tabs.versions.main', ['content' => PageVersion::version_table($page->id)])->render();
            list($tab_headers[-2], $tab_contents[-2]) = $page->tabRequests();
        }
        $tab_headers = array_filter($tab_headers);

        $tab_data = [
            'headers' => View::make('coaster::partials.tabs.header', ['tabs' => $tab_headers])->render(),
            'contents' => View::make('coaster::partials.tabs.content', ['tabs' => $tab_contents, 'item' => $item_name, 'new_page' => !$page->id, 'publishing' => $publishingOn, 'can_publish' => $auth['can_publish'], 'page' => $page])->render()
        ];

        if ($pageId > 0) {
            return View::make('coaster::pages.pages.edit', [
                'page' => $page,
                'page_lang' => $page_lang,
                'item_name' => $item_name,
                'publishingOn' => $publishingOn,
                'tab' => $tab_data,
                'frontendLink' => $frontendLink,
                'version' => $versionData,
                'auth' => $auth
            ]);
        } else {
            return View::make('coaster::pages.pages.add', [
                'page' => $page,
                'item_name' => $item_name,
                'tab' => $tab_data
            ]);
        }
    }

}
