<?php namespace CoasterCms\Http\Controllers\AdminControllers;

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
        $default_blocks = PageBlockDefault::preloadArray();

        list($tab_headers, $tab_contents) = Block::getTabs($blocks, $default_blocks);
        $tab_headers = array_filter($tab_headers);
        ksort($tab_headers);

        $tab_data = [
            'headers' => View::make('coaster::partials.tabs.header', ['tabs' => $tab_headers])->render(),
            'contents' => View::make('coaster::partials.tabs.content', ['tabs' => $tab_contents, 'item' => 'Site-wide Content', 'new_page' => false, 'publishing' => false, 'can_publish' => true])->render()
        ];

        $this->layoutData['title'] = 'Site-wide Content';
        $this->layoutData['content'] = View::make('coaster::pages.blocks', ['tab' => $tab_data]);
    }

    public function postIndex()
    {
        // update blocks
        AdminLog::new_log('Updated Site-wide Content');

        $versionId = PageVersion::add_new(0)->version_id;
        Block::submit(0, $versionId);

        $this->addAlert('success', 'Site-wide Content Updated');

        return \redirect()->route('coaster.admin.blocks');
    }

}