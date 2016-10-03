<?php namespace CoasterCms\Listeners\Admin;

use CoasterCms\Events\Admin\AuthRoute;
use CoasterCms\Models\PageGroup;
use Request;

class AuthRouteCheck
{

    /**
     * Handle the event.
     *
     * @param  AuthRoute $event
     * @return void
     */
    public function handle(AuthRoute $event)
    {
        // specify page_id option for use in auth check
        switch ($event->controller) {
            case 'forms':
            case 'gallery':
            case 'pages':
                $event->returnOptions['page_id'] = isset($event->parameters['pageId']) ? $event->parameters['pageId'] : 0;
                // use parent page id when posting add page form
                if ($event->action == 'add') {
                    $event->returnOptions['page_id'] = Request::input('page_info.parent.select') ?: $event->returnOptions['page_id'];
                    if (isset($event->parameters['groupId'])) {
                        $pageGroup = PageGroup::preload($event->parameters['groupId']);
                        if (!$pageGroup->exists || !$pageGroup->canAddItems()) {
                            $event->override = false;
                        }
                    }
                }
                // let page sort function deal with permissions
                if ($event->action == 'sort') {
                    $event->override = true;
                }
                break;
            case 'groups':
                $page_group = PageGroup::preload(isset($event->parameters['groupId']) ? $event->parameters['groupId'] : 0);
                $event->override = $page_group->exists ? $page_group->canAddItems() : $event->override;
        }
    }

}
