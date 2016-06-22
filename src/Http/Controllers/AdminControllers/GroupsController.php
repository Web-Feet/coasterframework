<?php namespace CoasterCms\Http\Controllers\AdminControllers;

use Auth;
use Carbon\Carbon;
use CoasterCms\Http\Controllers\AdminController as Controller;
use CoasterCms\Models\Block;
use CoasterCms\Models\Language;
use CoasterCms\Models\PageBlock;
use CoasterCms\Models\PageGroup;
use CoasterCms\Models\PageGroupAttribute;
use CoasterCms\Models\PageLang;
use View;

class GroupsController extends Controller
{

    public function get_pages($group_id)
    {
        if (!empty($group_id)) {
            $group = PageGroup::find($group_id);
            if (!empty($group)) {
                $page_ids = PageGroup::page_ids($group_id, false, true);
                $attributes = PageGroupAttribute::where('group_id', '=', $group_id)->get();
                $blocks = array();
                foreach ($attributes as $attribute) {
                    $blocks[] = Block::preload($attribute->item_block_id);
                }
                $page_info = array();
                $can_edit = [];
                $can_delete = [];
                if (!empty($page_ids)) {
                    foreach ($page_ids as $page_id) {
                        $page_info[$page_id] = new \stdClass;
                        $page_lang = PageLang::preload($page_id);
                        $page_info[$page_id]->name = $page_lang->name;
                        $page_info[$page_id]->id = $page_id;
                        $page_info[$page_id]->col = array();
                        foreach ($attributes as $k => $attribute) {
                            $page_block = PageBlock::preload_block($page_id, $attribute->item_block_id, $page_lang->live_version);
                            if (!empty($page_block)) {
                                $page_block = $page_block[Language::current()];
                            }
                            if (!empty($page_block)) {
                                if ($blocks[$k]->type == 'selectmultiple') {
                                    // selectmultiple
                                    $page_info[$page_id]->col[] = implode(', ', unserialize($page_block->content));
                                } elseif ($blocks[$k]->type == 'datetime') {
                                    // datetime
                                    $page_info[$page_id]->col[] = (new Carbon($page_block->content))->format('d/m/Y H:iA');
                                } else {
                                    // text/string/select
                                    $page_info[$page_id]->col[] = $page_block->content;
                                }
                            } else {
                                $page_info[$page_id]->col[] = '';
                            }
                        }
                        $can_edit[$page_id] = Auth::action('pages.edit', ['page_id' => $page_id]);
                        $can_delete[$page_id] = Auth::action('pages.delete', ['page_id' => $page_id]);
                    }
                }
                $pages = View::make('coaster::partials.groups.page_list', array('pages' => $page_info, 'item_name' => $group->item_name, 'blocks' => $blocks, 'can_edit' => $can_edit, 'can_delete' => $can_delete))->render();
                $this->layoutData['modals'] = View::make('coaster::modals.general.delete_item');
                $this->layoutData['content'] = View::make('coaster::pages.groups', array('group' => $group, 'pages' => $pages, 'can_add' => Auth::action('pages.add', ['page_id' => $group->default_parent])));
            }
        }
    }

}