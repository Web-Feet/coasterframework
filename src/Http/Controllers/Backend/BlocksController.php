<?php namespace CoasterCms\Http\Controllers\Backend;

use CoasterCms\Helpers\BlockManager;
use CoasterCms\Models\AdminLog;
use CoasterCms\Models\PageBlockDefault;
use CoasterCms\Models\PageVersion;
use CoasterCms\Models\Theme;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\View;

class BlocksController extends _Base
{

    public function get_index()
    {
        // load theme global blocks
        $blocks = array();
        $theme = Theme::find(config('coaster::frontend.theme'));
        if (!empty($theme)) {
            $blocks = Theme::theme_blocks($theme->id);
        }

        // load tab contents from categories & blocks (with default block contents)
        $default_blocks = PageBlockDefault::preload();
        $tab_data = BlockManager::tab_contents($blocks, $default_blocks, 'Site-wide Content', 'edit');

        $this->layout->title = 'Site-wide Content';
        $this->layout->content = View::make('coaster::pages.blocks', array('tab' => $tab_data));
    }

    public function post_index()
    {
        // update blocks
        AdminLog::new_log('Updated Site-wide Content');

        BlockManager::$to_version = PageVersion::add_new(0)->version_id;
        BlockManager::process_submission();

        $alert = new \stdClass;
        $alert->type = 'success';
        $alert->header = 'Site-wide Content Updated';
        $alert->content = '';
        $this->layout->alert = $alert;

        return Redirect::to(Request::url());
    }

}