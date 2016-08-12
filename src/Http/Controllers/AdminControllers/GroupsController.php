<?php namespace CoasterCms\Http\Controllers\AdminControllers;

use Auth;
use Carbon\Carbon;
use CoasterCms\Helpers\Cms\StringHelper;
use CoasterCms\Http\Controllers\AdminController as Controller;
use CoasterCms\Models\Block;
use CoasterCms\Models\Language;
use CoasterCms\Models\PageBlock;
use CoasterCms\Models\PageGroup;
use CoasterCms\Models\PageGroupAttribute;
use CoasterCms\Models\PageLang;
use CoasterCms\Models\Theme;
use View;

class GroupsController extends Controller
{

    public function getPages($groupId)
    {
        $group = PageGroup::find($groupId);
        if (!empty($group)) {
            $pageIds = $group->itemPageIds(false, true);

            $attributes = PageGroupAttribute::where('group_id', '=', $groupId)->get();
            $attributeBlocks = [];
            foreach ($attributes as $attribute) {
                if ($block = Block::preload($attribute->item_block_id)) {
                    $attributeBlocks[$attribute->item_block_id] = $block;
                }
            }

            $pageRows = '';

            if (!empty($pageIds)) {
                foreach ($pageIds as $pageId) {
                    $pageLang = PageLang::preload($pageId);

                    $showBlocks = [];
                    $canEdit = Auth::action('pages.edit', ['page_id' => $pageId]);
                    $canDelete = Auth::action('pages.delete', ['page_id' => $pageId]);

                    foreach ($attributeBlocks as $attributeBlock) {
                        $pageBlock = PageBlock::preload_block($pageId, $attributeBlock->id, $pageLang->live_version);
                        $pageBlockContent = !empty($pageBlock) ? $pageBlock[Language::current()]->content : '';
                        if ($attributeBlock->type == 'selectmultiple' && !empty($pageBlockContent)) {
                            // selectmultiple
                            $showBlocks[] = implode(', ', unserialize($pageBlockContent));
                        } elseif ($attributeBlock->type == 'datetime'&& !empty($pageBlockContent)) {
                            // datetime
                            $showBlocks[] = (new Carbon($pageBlockContent))->format(config('coaster::date.long'));
                        } else {
                            // text/string/select
                            $showBlocks[] = StringHelper::cutString($pageBlockContent, 50);
                        }
                    }

                    $pageRows .= View::make('coaster::partials.groups.page_row', array('page_lang' => $pageLang, 'item_name' => $group->item_name, 'showBlocks' => $showBlocks, 'can_edit' => $canEdit, 'can_delete' => $canDelete))->render();
                }
            }

            $pagesTable = View::make('coaster::partials.groups.page_table', array('rows' => $pageRows, 'item_name' => $group->item_name, 'blocks' => $attributeBlocks))->render();

            $this->layoutData['modals'] = View::make('coaster::modals.general.delete_item');
            $this->layoutData['content'] = View::make('coaster::pages.groups', array('group' => $group, 'pages' => $pagesTable, 'can_add' => $group->canAddItems()));
        }
    }

    public function getEdit($groupId)
    {
        $group = PageGroup::find($groupId);

        $templateSelectContent = new \stdClass;
        $templateSelectContent->options = [0 => '-- No default --'] + Theme::get_template_list($group->default_template);
        $templateSelectContent->selected = $group->default_template;
        
        $this->layoutData['content'] = View::make('coaster::pages.groups.edit', ['group' => $group, 'templateSelectContent' => $templateSelectContent]);
    }


    public function postEdit($groupId)
    {

        $this->getEdit($groupId);
    }


}