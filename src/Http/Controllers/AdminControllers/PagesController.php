<?php namespace CoasterCms\Http\Controllers\AdminControllers;

use Auth;
use CoasterCms\Helpers\Cms\DateTimeHelper;
use CoasterCms\Helpers\Cms\Page\Path;
use CoasterCms\Libraries\Blocks\Repeater;
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
use CoasterCms\Models\UserRole;
use Request;
use Response;
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

    public function getAdd($parentPageId = 0, $groupId = 0)
    {
        $publishingOn = config('coaster::admin.publishing') > 0;
        $cabPublish = ($publishingOn && Auth::action('pages.version-publish', ['page_id' => $parentPageId])) || (!$publishingOn && Auth::action('pages.edit', ['page_id' => $parentPageId]));

        // set page data
        $page = new Page;
        if ($parentPageId && $parent = Page::find($parentPageId)) {
            $page->parent = $parent->id;
            $page->template = $parent->child_template;
        } else {
            $page->parent = 0;
        }
        if ($groupId && $group = PageGroup::find($groupId)) {
            $page->groups->add($group);
            $page->template = $group->default_template;
            $page->parent = $parentPageId ? $page->parent : -1;
        }
        $page->group_container = 0;
        $page->link = 0;
        $page->live = $cabPublish ? 1 : 0;
        $page->sitemap = 1;

        // get item name, or default to page
        $item_name = $page->groupItemsNames() ?: 'Page';

        // get page info tab contents
        $tab_headers = [];
        $tab_contents = [];
        list($tab_headers[0], $tab_contents[0]) = $page->tabInfo();
        $tab_data = [
            'headers' => View::make('coaster::partials.tabs.header', ['tabs' => $tab_headers])->render(),
            'contents' => View::make('coaster::partials.tabs.content', ['tabs' => $tab_contents, 'item' => $item_name, 'new_page' => true, 'publishing' => $publishingOn, 'can_publish' => $cabPublish, 'page' => $page])->render()
        ];

        $this->layoutData['content'] = View::make('coaster::pages.pages.add', [
            'page' => $page,
            'item_name' => $item_name,
            'tab' => $tab_data
        ]);
    }

    public function postAdd($pageId = 0, $groupId = 0)
    {
        $page_version = PageVersion::prepareNew();
        $page_info = Request::input('page_info') ?: [];
        $page_info_lang = Request::input('page_info_lang') ?: [];
        $page_groups = Request::input('page_groups') ?: [];
        $page_info_other = Request::input('page_info_other') ?: [];
        $page = new Page;

        if (!$page->savePostData($page_version, $page_info, $page_info_lang, $page_groups, $page_info_other)) {
            $this->getAdd($pageId);
            return null;
        } else {
            AdminLog::new_log('Added page \'' . $page->pageCurrentLang->name . '\' (Page ID ' . $page->id . ')');
            return \redirect()->route('coaster.admin.pages.edit', ['pageId' => $page->id]);
        }
    }

    public function getEdit($pageId, $versionId = 0)
    {
        // get page data
        if (!($page = Page::find($pageId))) {
            return 'Page Not Found';
        }
        PageVersionSchedule::checkPageVersionIds();

        $publishingOn = config('coaster::admin.publishing') > 0;
        $auth = [
            'can_publish' => ($publishingOn && Auth::action('pages.version-publish', ['page_id' => $pageId])) || (!$publishingOn && Auth::action('pages.edit', ['page_id' => $pageId])),
            'can_duplicate' => $page->canDuplicate()
        ];

        // get page lang data
        if (!($page_lang = $page->pageCurrentLang)) {
            if (!($page_lang = $page->pageDefaultLang)) {
                return 'Page Lang Data Not Found';
            }
            $page_lang = $page_lang->replicate();
            $page_lang->language_id = Language::current();
            $page_lang->save();
        }
        $page_lang->url = ltrim($page_lang->url, '/');

        // get version data
        $versionData = [];
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
            if ($page_version = PageVersion::where('version_id', '=', $versionData['editing'])->where('page_id', '=', $pageId)->first()) {
                $page->template = $page_version->template;
            } else {
                return 'Page Version Data Not Found';
            }
        }

        // load blocks content
        if ($page->link == 0) {
            $blocks = Template::template_blocks(config('coaster::frontend.theme'), $page->template);
            $blocks_content = PageBlock::preloadPage($pageId, $versionData['editing']);
            list($tab_headers, $tab_contents) = Block::getTabs($blocks, $blocks_content, $page->id);
        } else {
            $tab_headers = [];
            $tab_contents = [];
        }

        // load page info and order so page info is first and block categories are in order
        list($tab_headers[0], $tab_contents[0]) = $page->tabInfo();
        ksort($tab_headers);

        // load version / publish requests
        if ($publishingOn && count($tab_headers) > 1) {
            $tab_headers[-1] = 'Versions';
            $tab_contents[-1] = View::make('coaster::partials.tabs.versions.main', ['content' => PageVersion::version_table($page->id)])->render();
            list($tab_headers[-2], $tab_contents[-2]) = $page->tabRequests();
        }

        // remove empty tabs
        $tab_headers = array_filter($tab_headers);

        // get item name, or default to page
        $item_name = $page->groupItemsNames() ?: 'Page';

        $tab_data = [
            'headers' => View::make('coaster::partials.tabs.header', ['tabs' => $tab_headers])->render(),
            'contents' => View::make('coaster::partials.tabs.content', ['tabs' => $tab_contents, 'item' => $item_name, 'new_page' => false, 'publishing' => $publishingOn, 'can_publish' => $auth['can_publish'], 'page' => $page])->render()
        ];

        // add required modals
        if ($publishingOn) {
            $intervals = PageVersionSchedule::selectOptions();
            $this->layoutData['modals'] =
                View::make('coaster::modals.pages.publish')->render() .
                View::make('coaster::modals.pages.publish_schedule', ['intervals' => $intervals, 'live_version' => $versionData['live']])->render() .
                View::make('coaster::modals.pages.request_publish')->render() .
                View::make('coaster::modals.pages.rename_version')->render();
        }

        $this->layoutData['content'] = View::make('coaster::pages.pages.edit', [
            'page' => $page,
            'page_lang' => $page_lang,
            'item_name' => $item_name,
            'publishingOn' => $publishingOn,
            'tab' => $tab_data,
            'frontendLink' => $frontendLink,
            'version' => $versionData,
            'auth' => $auth
        ]);
        return null;
    }

    public function postEdit($pageId)
    {
        $existingPage = Page::find($pageId);
        if (!$existingPage) {
            return 'Page not found';
        }

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

        $page_info = Request::input('page_info') ?: [];
        $page_info_lang = Request::input('page_info_lang') ?: [];
        $page_groups = Request::input('page_groups') ?: [];
        $page_info_other = Request::input('page_info_other') ?: [];

        // run if duplicate button was hit
        if (Request::input('duplicate') == 1) {
            if ($existingPage->canDuplicate()) {
                if ($duplicatePage = $existingPage->saveDuplicateFromPostData($page_info, $page_info_lang, $page_groups, $page_info_other)) {
                    Repeater::setDuplicate();
                    Block::submit($duplicatePage->id, 1, $publish);
                    return \redirect()->route('coaster.admin.pages.edit', ['pageId' => $duplicatePage->id]);
                } else {
                    $this->addAlert('danger', 'Duplication failed');
                    return $this->getEdit($pageId);
                }
            } else {
                return abort(403, 'Action not permitted');
            }
        }

        $version = PageVersion::add_new($pageId);

        // save page info
        if ($existingPage->savePostData($version, $page_info, $page_info_lang, $page_groups, $page_info_other)) {
            AdminLog::new_log('Updated page \'' . $existingPage->pageLang()->name . '\' (Page ID ' . $existingPage->id . ')');
        } else {
            $this->addAlert('warning', '"Page Info" not updated (check tab for errors)');
        }

        // update blocks
        Block::submit($pageId, $version->version_id, $publish);
        $this->addAlert('success', 'Page Content Updated');

        if ($publish) {
            if (Request::input('publish_request') != '') {
                PagePublishRequests::add($pageId, $version->version_id, Request::input('request_note'));
            }
            $version->publish();
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
        if ($page = Page::find($pageId)) {
            return json_encode($page->delete());
        }
        return Response::make('Page with ID '.$pageId.' not found', 500);
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

}
