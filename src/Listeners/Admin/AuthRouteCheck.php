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
                $event->returnOptions['page_id'] = isset($parameters['pageId']) ? $parameters['pageId'] : 0;
                // use parent page id when posting add page form
                if ($event->action == 'add') {
                    $event->returnOptions['page_id'] = Request::input('page_info.parent') ?: $event->returnOptions['page_id'];
                }
                // let page sort function deal with permissions
                if ($event->action == 'sort') {
                    $event->ignore = true;
                }
                break;
            case 'groups':
                $page_group = PageGroup::find(isset($parameters['groupId']) ? $parameters['groupId'] : 0);
                $event->returnOptions['page_id'] = !empty($page_group) ? $page_group->default_parent : 0;
        }
    }

}
