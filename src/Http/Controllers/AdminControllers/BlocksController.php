<?php namespace CoasterCms\Http\Controllers\AdminControllers;

use CoasterCms\Helpers\Cms\Theme\BlockManager;
use CoasterCms\Http\Controllers\AdminController as Controller;
use CoasterCms\Models\AdminLog;
use CoasterCms\Models\Block;
use CoasterCms\Models\PageBlockDefault;
use CoasterCms\Models\PageVersion;
use CoasterCms\Models\Theme;
use View;

class BlocksController extends Controller
{

    public function getIndex()
    {
        // load theme global blocks
        $blocks = array();
        $theme = Theme::find(config('coaster::frontend.theme'));
        if (!empty($theme)) {
            $blocks = Theme::theme_blocks($theme->id);
        }

        // load tab contents from categories & blocks (with default block contents)
        $default_blocks = PageBlockDefault::preload();
        $tab_data = BlockManager::tab_contents($blocks, $default_blocks, 'Site-wide Content');

        $this->layoutData['title'] = 'Site-wide Content';
        $this->layoutData['content'] = View::make('coaster::pages.blocks', array('tab' => $tab_data));
    }

    public function postIndex()
    {
        // update blocks
        AdminLog::new_log('Updated Site-wide Content');

        $versionId = PageVersion::add_new(0)->version_id;
        Block::submit(0, $versionId);

        $this->setAlert('success', 'Site-wide Content Updated');

        return \redirect()->route('coaster.admin.blocks');
    }

}